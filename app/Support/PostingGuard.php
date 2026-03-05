<?php

namespace App\Support;

use App\Models\Ban;
use App\Models\Board;
use App\Models\Post;
use App\Models\PostMeta;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PostingGuard
{
    public const EPOCH = 'auth-v1';

    public static function abuseId(?int $userId): string
    {
        return 'u:'.($userId ?: 0);
    }

    public static function ensureNotBanned(string $abuseId): void
    {
        $activeBan = Ban::query()
            ->where('abuse_id', $abuseId)
            ->where('epoch', self::EPOCH)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        if (! $activeBan) {
            return;
        }

        $until = $activeBan->expires_at instanceof Carbon
            ? $activeBan->expires_at->format('Y-m-d H:i')
            : null;

        throw ValidationException::withMessages([
            'body' => $until
                ? __('ui.posting_banned_until', ['datetime' => $until])
                : __('ui.posting_banned'),
        ]);
    }

    public static function enforceRateLimit(Board $board, string $abuseId): void
    {
        $limitCount = max(1, (int) ($board->post_rate_limit_count ?? 3));
        $windowSeconds = max(5, (int) ($board->post_rate_limit_window_seconds ?? 60));

        $recentCount = PostMeta::query()
            ->where('abuse_id', $abuseId)
            ->where('epoch', self::EPOCH)
            ->whereHas('post', function ($query) use ($board, $windowSeconds) {
                $query->where('created_at', '>=', now()->subSeconds($windowSeconds))
                    ->whereHas('thread', fn ($threadQ) => $threadQ->where('board_id', $board->id));
            })
            ->count();

        if ($recentCount < $limitCount) {
            return;
        }

        throw ValidationException::withMessages([
            'body' => __('ui.posting_rate_limited', ['count' => $limitCount, 'seconds' => $windowSeconds]),
        ]);
    }

    public static function stampPost(Post $post, string $abuseId): void
    {
        PostMeta::updateOrCreate(
            ['post_id' => $post->id],
            ['abuse_id' => $abuseId, 'epoch' => self::EPOCH]
        );
    }
}
