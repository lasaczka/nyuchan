<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\ModAction;
use App\Models\Post;
use App\Models\PostAttachment;
use App\Models\Thread;
use App\Services\AttachmentStorage;
use App\Support\PostFormatter;
use App\Support\PostingGuard;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThreadController extends Controller
{
    public function __construct(private readonly AttachmentStorage $attachments)
    {
    }

    public function show(Request $request, Board $board, Thread $thread)
    {
        abort_unless($thread->board_id === $board->id, 404);

        $quotePostId = max(0, $request->integer('quote'));

        $posts = Post::where('thread_id', $thread->id)
            ->with(['attachments', 'meta'])
            ->orderBy('id')
            ->get();
        $opPostId = $posts->first()?->id;
        $opAbuseId = $posts->first()?->meta?->abuse_id;

        $quoteLinks = $this->buildQuoteLinks(PostFormatter::extractQuoteIds($posts->pluck('body')->all()));
        $deleteReasons = $this->loadDeleteReasons($posts->where('is_deleted', true)->pluck('id')->all());

        $posts->each(function (Post $post) use ($quoteLinks, $thread, $deleteReasons, $opPostId, $opAbuseId): void {
            $post->delete_reason = $deleteReasons[$post->id] ?? null;
            $post->is_op_in_thread = ($opAbuseId !== null && $post->meta?->abuse_id === $opAbuseId)
                || ((int) $post->id === (int) $opPostId);

            if ($post->is_deleted) {
                $post->rendered_body = '';

                return;
            }

            $post->rendered_body = PostFormatter::format(
                $post->body,
                function (int $postId) use ($quoteLinks, $thread, $opPostId): ?array {
                    $target = $quoteLinks[$postId] ?? null;

                    if (! $target) {
                        return null;
                    }

                    $isOpQuote = $opPostId !== null && $postId === (int) $opPostId;

                    return [
                        'href' => $target['href'],
                        'new_tab' => (int) $target['thread_id'] !== (int) $thread->id,
                        'label' => $isOpQuote ? __('ui.op_short') : null,
                    ];
                }
            );
        });

        return view('threads.show', [
            'board' => $board,
            'thread' => $thread,
            'posts' => $posts,
            'quotePostId' => $quotePostId > 0 ? $quotePostId : null,
        ]);
    }

    public function store(Request $request, Board $board)
    {
        $imageMaxKb = max(1, (int) floor(((int) config('nyuchan.attachments_input_max_bytes', 8 * 1024 * 1024)) / 1024));
        $maxFiles = max(1, (int) config('nyuchan.attachments_max_files', 4));
        $phpUploadMax = $this->formatIniSizeToBytes((string) ini_get('upload_max_filesize'));
        $phpPostMax = $this->formatIniSizeToBytes((string) ini_get('post_max_size'));
        $phpEffectiveMax = max(1, min($phpUploadMax, $phpPostMax));

        if ($macroResponse = $this->macroInsertResponse($request)) {
            return $macroResponse;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'body' => ['required', 'string', 'max:5000'],
            'use_display_name' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:'.$maxFiles],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.$imageMaxKb],
        ], [
            'images.max' => __('ui.image_max_files', ['count' => $maxFiles]),
            'images.*.uploaded' => __('ui.image_upload_failed_php', ['size' => $this->formatBytes($phpEffectiveMax)]),
            'images.*.max' => __('ui.image_too_large_input', ['size' => $this->formatBytes($imageMaxKb * 1024)]),
        ]);

        $abuseId = PostingGuard::abuseId($request->user()?->id);
        PostingGuard::ensureNotBanned($abuseId);
        PostingGuard::enforceRateLimit($board, $abuseId);

        return DB::transaction(function () use ($data, $board, $request, $abuseId) {
            $ownerToken = Str::random(32);

            $thread = Thread::create([
                'board_id' => $board->id,
                'title' => $data['title'],
                'bumped_at' => now(),
                'is_locked' => false,
                'owner_token_hash' => hash('sha256', $ownerToken),
                'owner_token_issued_at' => now(),
            ]);

            $post = Post::create([
                'thread_id' => $thread->id,
                'display_name' => ! empty($data['use_display_name']) ? ($request->user()?->username ?: null) : null,
                'display_color' => ! empty($data['use_display_name']) ? ($request->user()?->profile_color ?: null) : null,
                'body' => $data['body'],
                'is_deleted' => false,
            ]);

            PostingGuard::stampPost($post, $abuseId);

            if ($request->hasFile('images')) {
                foreach ($request->file('images', []) as $imageFile) {
                    if ($imageFile instanceof UploadedFile) {
                        $this->createAttachment($post, $imageFile);
                    }
                }
            }

            Cookie::queue(
                cookie()->make(
                    'thread_owner_'.$thread->id,
                    $ownerToken,
                    60 * 24 * 365,
                    null,
                    null,
                    true,
                    true,
                    false,
                    'Lax'
                )
            );

            $this->enforceThreadLimit($board, $thread->id);

            return redirect()->route('threads.show', [
                'board' => $board->slug,
                'thread' => $thread->id,
            ]);
        });
    }

    private function buildQuoteLinks(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        return Post::query()
            ->whereIn('id', $postIds)
            ->with(['thread.board'])
            ->get()
            ->mapWithKeys(function (Post $post): array {
                $boardSlug = $post->thread?->board?->slug;
                $threadId = $post->thread?->id;

                if (! $boardSlug || ! $threadId) {
                    return [];
                }

                return [
                    $post->id => [
                        'href' => route('threads.show', ['board' => $boardSlug, 'thread' => $threadId]).'#p'.$post->id,
                        'thread_id' => (int) $threadId,
                    ],
                ];
            })
            ->all();
    }

    private function loadDeleteReasons(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        return ModAction::query()
            ->where('action', 'delete_post')
            ->where('target_type', Post::class)
            ->whereIn('target_id', $postIds)
            ->orderByDesc('id')
            ->get()
            ->unique('target_id')
            ->mapWithKeys(function (ModAction $action): array {
                $reason = trim((string) $action->reason);

                if ($reason === '' || $reason === 'no reason') {
                    $reason = null;
                }

                return [(int) $action->target_id => $reason];
            })
            ->all();
    }

    private function createAttachment(Post $post, UploadedFile $file): void
    {
        $stored = $this->attachments->storeUploadedFile($file);

        PostAttachment::create([
            'post_id' => $post->id,
            'path' => $stored['path'],
            'thumb_path' => $stored['thumb_path'],
            'original_name' => $stored['original_name'],
            'mime' => $stored['mime'],
            'size' => $stored['size'],
            'width' => $stored['width'],
            'height' => $stored['height'],
            'thumb_width' => $stored['thumb_width'],
            'thumb_height' => $stored['thumb_height'],
        ]);
    }

    private function macroInsertResponse(Request $request)
    {
        $insertMacro = (string) $request->input('insert_macro', '');
        if ($insertMacro === '') {
            return null;
        }

        $macro = $this->macroTemplate($insertMacro);
        if ($macro === null) {
            return null;
        }

        $body = (string) $request->input('body', '');
        $separator = $body !== '' && ! str_ends_with($body, "\n") ? "\n" : '';

        return back()->withInput(array_merge(
            $request->except('insert_macro'),
            ['body' => $body.$separator.$macro]
        ));
    }

    private function macroTemplate(string $key): ?string
    {
        return match ($key) {
            'reply' => '>>123 ',
            'greentext' => '>',
            'bold' => '**text**',
            'italic' => '*text*',
            'strike' => '~~text~~',
            'underline' => '__text__',
            'spoiler' => '||spoiler||',
            default => null,
        };
    }

    private function formatIniSizeToBytes(string $value): int
    {
        $v = trim($value);
        if ($v === '') {
            return 0;
        }

        $unit = strtolower(substr($v, -1));
        $num = (float) $v;

        return (int) match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (int) $num,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / (1024 * 1024), 2, '.', ''), '0'), '.').' MB';
        }

        return rtrim(rtrim(number_format($bytes / 1024, 2, '.', ''), '0'), '.').' KB';
    }

    private function enforceThreadLimit(Board $board, int $newThreadId): void
    {
        $threadLimit = max(1, (int) ($board->thread_limit ?? 100));
        $totalThreads = Thread::query()
            ->where('board_id', $board->id)
            ->count();

        $overflow = $totalThreads - $threadLimit;
        if ($overflow <= 0) {
            return;
        }

        $idsToDelete = Thread::query()
            ->where('board_id', $board->id)
            ->where('id', '!=', $newThreadId)
            ->orderBy('bumped_at')
            ->orderBy('id')
            ->limit($overflow)
            ->pluck('id')
            ->all();

        if ($idsToDelete !== []) {
            Thread::query()->whereIn('id', $idsToDelete)->delete();
        }
    }
}
