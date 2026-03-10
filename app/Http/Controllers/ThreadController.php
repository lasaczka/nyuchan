<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Post;
use App\Models\PostAttachment;
use App\Models\Thread;
use App\Services\AttachmentStorage;
use App\Services\PostMacroService;
use App\Services\PostDeleteReasonService;
use App\Services\PostFormatter;
use App\Services\QuoteLinkResolver;
use App\Services\ThreadFavoritesService;
use App\Support\PostingGuard;
use App\ValueObjects\AttachmentUploadLimits;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThreadController extends Controller
{
    public function __construct(
        private readonly AttachmentStorage $attachments,
        private readonly PostFormatter $formatter,
        private readonly QuoteLinkResolver $quoteLinkResolver,
        private readonly ThreadFavoritesService $threadFavorites,
        private readonly PostDeleteReasonService $deleteReasonService,
        private readonly PostMacroService $postMacro,
    ) {
    }

    public function show(Request $request, Board $board, Thread $thread)
    {
        abort_unless($thread->board_id === $board->id, 404);
        $isFavorite = auth()->check() && $this->threadFavorites->isFavorite(auth()->user(), $thread);

        $quotePostId = max(0, $request->integer('quote'));

        $posts = Post::where('thread_id', $thread->id)
            ->with(['attachments', 'meta'])
            ->orderBy('id')
            ->get();
        $opPostId = $posts->first()?->id;
        $opAbuseId = $posts->first()?->meta?->abuse_id;

        $quoteLinks = $this->quoteLinkResolver->buildQuoteLinks($this->formatter->extractQuoteIds($posts->pluck('body')->all()));
        $deleteReasons = $this->deleteReasonService->loadForPosts($posts->where('is_deleted', true)->pluck('id')->all());

        $posts->each(function (Post $post) use ($quoteLinks, $thread, $deleteReasons, $opPostId, $opAbuseId): void {
            $post->delete_reason = $deleteReasons[$post->id] ?? null;
            $post->is_op_in_thread = ($opAbuseId !== null && $post->meta?->abuse_id === $opAbuseId)
                || ((int) $post->id === (int) $opPostId);

            if ($post->is_deleted) {
                $post->rendered_body = '';

                return;
            }

            $post->rendered_body = $this->formatter->format(
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
            'isFavorite' => $isFavorite,
        ]);
    }

    public function store(Request $request, Board $board)
    {
        $uploadLimits = AttachmentUploadLimits::fromRuntime();

        if ($macroResponse = $this->macroInsertResponse($request)) {
            return $macroResponse;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'body' => ['required', 'string', 'max:5000'],
            'use_display_name' => ['nullable', 'boolean'],
            'strip_metadata' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:'.$uploadLimits->maxFiles()],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.$uploadLimits->imageMaxKb()],
        ], [
            'images.max' => __('ui.image_max_files', ['count' => $uploadLimits->maxFiles()]),
            'images.*.uploaded' => __('ui.image_upload_failed_php', ['size' => $uploadLimits->phpEffectiveMaxLabel()]),
            'images.*.max' => __('ui.image_too_large_input', ['size' => $uploadLimits->imageMaxLabel()]),
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
                        $this->createAttachment($post, $imageFile, ! empty($data['strip_metadata']));
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

    private function createAttachment(Post $post, UploadedFile $file, bool $stripMetadata): void
    {
        $stored = $this->attachments->storeUploadedFile($file, $stripMetadata);

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

        $macro = $this->postMacro->resolveTemplate($insertMacro);
        if ($macro === null) {
            return null;
        }

        $body = (string) $request->input('body', '');

        return back()->withInput(array_merge(
            $request->except('insert_macro'),
            ['body' => $this->postMacro->appendToBody($body, $macro)]
        ));
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
