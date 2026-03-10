<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use App\Models\Board;
use App\Models\ModAction;
use App\Models\Post;
use App\Models\Thread;
use App\Support\PostingGuard;
use App\ValueObjects\AbuseId;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    private const int BAN_REASON_MAX_LENGTH = 1000;
    private const int BAN_MINUTES_MIN = 5;
    private const int BAN_MINUTES_MAX = 43200;

    public function updateBoardSettings(Request $request, Board $board)
    {
        $user = $request->user();
        abort_unless($user && $user->canBanUsers(), 403);

        $data = $request->validate([
            'thread_limit' => ['required', 'integer', 'min:10', 'max:5000'],
            'bump_limit' => ['required', 'integer', 'min:10', 'max:1000'],
            'post_rate_limit_count' => ['required', 'integer', 'min:1', 'max:30'],
            'post_rate_limit_window_seconds' => ['required', 'integer', 'min:5', 'max:3600'],
        ]);

        $board->update($data);

        ModAction::create([
            'actor_user_id' => $user->id,
            'action' => 'update_board_settings',
            'target_type' => Board::class,
            'target_id' => $board->id,
            'reason' => json_encode($data, JSON_UNESCAPED_UNICODE) ?: 'settings update',
        ]);

        return back()->with('status', __('ui.board_settings_updated'));
    }

    public function deletePost(Request $request, Board $board, Thread $thread, Post $post)
    {
        $user = $request->user();
        abort_unless($user && $user->canModeratePosts(), 403);
        abort_unless($thread->board_id === $board->id && $post->thread_id === $thread->id, 404);

        $reason = trim((string) $request->input('reason', ''));

        $post->update([
            'display_name' => null,
            'body' => '',
            'is_deleted' => true,
        ]);

        ModAction::create([
            'actor_user_id' => $user->id,
            'action' => 'delete_post',
            'target_type' => Post::class,
            'target_id' => $post->id,
            'reason' => $reason !== '' ? $reason : 'no reason',
        ]);

        return back()->with('status', __('ui.post_deleted'));
    }

    public function banPostAuthor(Request $request, Board $board, Thread $thread, Post $post)
    {
        $user = $request->user();
        abort_unless($user && $user->canBanUsers(), 403);
        abort_unless($thread->board_id === $board->id && $post->thread_id === $thread->id, 404);

        $data = $this->validateBanPayload($request);

        [$targetAbuseId, $banErrorKey] = $this->resolveAndValidateTargetAbuseId($post->meta?->abuse_id, (int) $user->id);
        if (! $targetAbuseId) {
            return back()->withErrors(['ban' => __($banErrorKey ?? 'ui.cannot_ban_unknown_author')]);
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        $expiresAt = $this->banByAbuseId($targetAbuseId, $reason, (int) $data['minutes'], $user->id);

        if (! $post->is_deleted) {
            $post->update([
                'display_name' => null,
                'body' => '',
                'is_deleted' => true,
            ]);
        }

        ModAction::create([
            'actor_user_id' => $user->id,
            'action' => 'ban_author',
            'target_type' => Post::class,
            'target_id' => $post->id,
            'reason' => $reason,
        ]);

        ModAction::create([
            'actor_user_id' => $user->id,
            'action' => 'delete_post',
            'target_type' => Post::class,
            'target_id' => $post->id,
            'reason' => $reason,
        ]);

        return back()->with('status', __('ui.author_banned_until', ['datetime' => $expiresAt->format('Y-m-d H:i')]));
    }

    public function deleteThread(Request $request, Board $board, Thread $thread)
    {
        $user = $request->user();
        abort_unless($user && $user->canModeratePosts(), 403);
        abort_unless($thread->board_id === $board->id, 404);

        $reason = trim((string) $request->input('reason', ''));
        $threadId = $thread->id;

        $thread->delete();

        ModAction::create([
            'actor_user_id' => $user->id,
            'action' => 'delete_thread',
            'target_type' => Thread::class,
            'target_id' => $threadId,
            'reason' => $reason !== '' ? $reason : 'no reason',
        ]);

        return redirect()->route('boards.show', ['board' => $board->slug])
            ->with('status', __('ui.thread_deleted'));
    }

    public function banThreadAuthor(Request $request, Board $board, Thread $thread)
    {
        $user = $request->user();
        abort_unless($user && $user->canBanUsers(), 403);
        abort_unless($thread->board_id === $board->id, 404);

        $data = $this->validateBanPayload($request);

        $op = $thread->posts()->with('meta')->orderBy('id')->first();
        [$targetAbuseId, $banErrorKey] = $this->resolveAndValidateTargetAbuseId($op?->meta?->abuse_id, (int) $user->id);
        if (! $targetAbuseId) {
            return back()->withErrors(['ban' => __($banErrorKey ?? 'ui.cannot_ban_unknown_author')]);
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        $expiresAt = $this->banByAbuseId($targetAbuseId, $reason, (int) $data['minutes'], $user->id);
        $threadId = $thread->id;
        $thread->delete();

        ModAction::create([
            'actor_user_id' => $user->id,
            'action' => 'ban_thread_author',
            'target_type' => Thread::class,
            'target_id' => $threadId,
            'reason' => $reason,
        ]);
        ModAction::create([
            'actor_user_id' => $user->id,
            'action' => 'delete_thread',
            'target_type' => Thread::class,
            'target_id' => $threadId,
            'reason' => $reason,
        ]);

        return redirect()->route('boards.show', ['board' => $board->slug])
            ->with('status', __('ui.author_banned_until', ['datetime' => $expiresAt->format('Y-m-d H:i')]));
    }

    private function banByAbuseId(AbuseId $abuseId, string $reason, int $minutes, int $actorId)
    {
        $expiresAt = now()->addMinutes($minutes);

        Ban::create([
            'abuse_id' => $abuseId->value(),
            'epoch' => PostingGuard::EPOCH,
            'reason' => $reason,
            'expires_at' => $expiresAt,
            'created_by_user_id' => $actorId,
        ]);

        return $expiresAt;
    }

    private function resolveAndValidateTargetAbuseId(?string $storedAbuseId, int $actorUserId): array
    {
        if (! $storedAbuseId) {
            return [null, 'ui.cannot_ban_unknown_author'];
        }

        $targetAbuseId = AbuseId::fromStored($storedAbuseId);
        if ($targetAbuseId->equals(PostingGuard::abuseId($actorUserId))) {
            return [null, 'ui.cannot_ban_self'];
        }

        return [$targetAbuseId, null];
    }

    private function validateBanPayload(Request $request): array
    {
        return $request->validate([
            'reason' => ['nullable', 'string', 'max:'.self::BAN_REASON_MAX_LENGTH],
            'minutes' => ['required', 'integer', 'min:'.self::BAN_MINUTES_MIN, 'max:'.self::BAN_MINUTES_MAX],
        ]);
    }
}
