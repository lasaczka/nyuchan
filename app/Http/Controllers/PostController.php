<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Post;
use App\Models\PostAttachment;
use App\Models\Thread;
use App\Services\AttachmentStorage;
use App\Services\PostMacroService;
use App\Support\PostingGuard;
use App\ValueObjects\AttachmentUploadLimits;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PostController extends Controller
{
    private const int DEFAULT_POST_BODY_MAX_LENGTH = 5000;

    public function __construct(
        private readonly AttachmentStorage $attachments,
        private readonly PostMacroService $postMacro,
    ) {
    }

    public function store(Request $request, Board $board, Thread $thread)
    {
        abort_unless($thread->board_id === $board->id, 404);
        abort_if($thread->is_locked, 403);
        $bodyMaxLength = max(1, (int) config('nyuchan.post_body_max_length', self::DEFAULT_POST_BODY_MAX_LENGTH));
        $uploadLimits = AttachmentUploadLimits::fromRuntime();

        if ($macroResponse = $this->macroInsertResponse($request)) {
            return $macroResponse;
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:'.$bodyMaxLength],
            'use_display_name' => ['nullable', 'boolean'],
            'sage' => ['nullable', 'boolean'],
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

        $post = DB::transaction(function () use ($data, $thread, $request, $board, $abuseId) {
            $preCount = $thread->posts()->count();

            $payload = [
                'thread_id' => $thread->id,
                'display_name' => ! empty($data['use_display_name']) ? ($request->user()?->username ?: null) : null,
                'display_color' => ! empty($data['use_display_name']) ? ($request->user()?->profile_color ?: null) : null,
                'body' => $data['body'],
                'is_deleted' => false,
            ];

            if ($this->supportsSageColumn()) {
                $payload['is_sage'] = ! empty($data['sage']);
            }

            $post = Post::create($payload);

            PostingGuard::stampPost($post, $abuseId);

            if ($request->hasFile('images')) {
                foreach ($request->file('images', []) as $imageFile) {
                    if ($imageFile instanceof UploadedFile) {
                        $this->createAttachment($post, $imageFile);
                    }
                }
            }

            if (empty($data['sage']) && $preCount < max(1, (int) $board->bump_limit)) {
                $thread->update(['bumped_at' => now()]);
            }

            return $post;
        });

        return redirect()->route('threads.show', [
            'board' => $board->slug,
            'thread' => $thread->id,
        ])->withFragment('p'.$post->id);
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

    private function supportsSageColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('posts', 'is_sage');
        }

        return $hasColumn;
    }
}
