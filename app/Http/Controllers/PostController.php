<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Post;
use App\Models\PostAttachment;
use App\Models\Thread;
use App\Services\AttachmentStorage;
use App\Support\PostingGuard;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PostController extends Controller
{
    public function __construct(private readonly AttachmentStorage $attachments)
    {
    }

    public function store(Request $request, Board $board, Thread $thread)
    {
        abort_unless($thread->board_id === $board->id, 404);
        abort_if($thread->is_locked, 403);
        $imageMaxKb = max(1, (int) floor(((int) config('nyuchan.attachments_input_max_bytes', 8 * 1024 * 1024)) / 1024));
        $maxFiles = max(1, (int) config('nyuchan.attachments_max_files', 4));
        $phpUploadMax = $this->formatIniSizeToBytes((string) ini_get('upload_max_filesize'));
        $phpPostMax = $this->formatIniSizeToBytes((string) ini_get('post_max_size'));
        $phpEffectiveMax = max(1, min($phpUploadMax, $phpPostMax));

        if ($macroResponse = $this->macroInsertResponse($request)) {
            return $macroResponse;
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'use_display_name' => ['nullable', 'boolean'],
            'sage' => ['nullable', 'boolean'],
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

    private function supportsSageColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('posts', 'is_sage');
        }

        return $hasColumn;
    }
}
