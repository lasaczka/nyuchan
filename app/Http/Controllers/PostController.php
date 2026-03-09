<?php

namespace App\Http\Controllers;

use App\Enums\PostMarkup;
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
    private const string MACRO_REPLY = 'reply';
    private const string MACRO_GREENTEXT = 'greentext';

    private const int DEFAULT_POST_BODY_MAX_LENGTH = 5000;
    private const int DEFAULT_ATTACHMENTS_INPUT_MAX_BYTES = 8 * 1024 * 1024;
    private const int DEFAULT_ATTACHMENTS_MAX_FILES = 4;
    private const int MIN_UPLOAD_KB = 1;
    private const int BYTES_IN_KB = 1024;
    private const int BYTES_IN_MB = 1024 * 1024;
    private const int BYTES_IN_GB = 1024 * 1024 * 1024;

    public function __construct(private readonly AttachmentStorage $attachments)
    {
    }

    public function store(Request $request, Board $board, Thread $thread)
    {
        abort_unless($thread->board_id === $board->id, 404);
        abort_if($thread->is_locked, 403);
        $bodyMaxLength = max(1, (int) config('nyuchan.post_body_max_length', self::DEFAULT_POST_BODY_MAX_LENGTH));
        $imageMaxKb = max(
            self::MIN_UPLOAD_KB,
            (int) floor(((int) config('nyuchan.attachments_input_max_bytes', self::DEFAULT_ATTACHMENTS_INPUT_MAX_BYTES)) / self::BYTES_IN_KB)
        );
        $maxFiles = max(self::MIN_UPLOAD_KB, (int) config('nyuchan.attachments_max_files', self::DEFAULT_ATTACHMENTS_MAX_FILES));
        $phpUploadMax = $this->formatIniSizeToBytes((string) ini_get('upload_max_filesize'));
        $phpPostMax = $this->formatIniSizeToBytes((string) ini_get('post_max_size'));
        $phpEffectiveMax = max(self::MIN_UPLOAD_KB, min($phpUploadMax, $phpPostMax));

        if ($macroResponse = $this->macroInsertResponse($request)) {
            return $macroResponse;
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:'.$bodyMaxLength],
            'use_display_name' => ['nullable', 'boolean'],
            'sage' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:'.$maxFiles],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.$imageMaxKb],
        ], [
            'images.max' => __('ui.image_max_files', ['count' => $maxFiles]),
            'images.*.uploaded' => __('ui.image_upload_failed_php', ['size' => $this->formatBytes($phpEffectiveMax)]),
            'images.*.max' => __('ui.image_too_large_input', ['size' => $this->formatBytes($imageMaxKb * self::BYTES_IN_KB)]),
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

        $macro = $this->resolveMacroTemplate($insertMacro);
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

    private function resolveMacroTemplate(string $insertMacro): ?string
    {
        return match ($insertMacro) {
            self::MACRO_REPLY => '>>123 ',
            self::MACRO_GREENTEXT => '>',
            default => PostMarkup::templateFor($insertMacro),
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
            'g' => $num * self::BYTES_IN_GB,
            'm' => $num * self::BYTES_IN_MB,
            'k' => $num * self::BYTES_IN_KB,
            default => (int) $num,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= self::BYTES_IN_MB) {
            return rtrim(rtrim(number_format($bytes / self::BYTES_IN_MB, 2, '.', ''), '0'), '.').' MB';
        }

        return rtrim(rtrim(number_format($bytes / self::BYTES_IN_KB, 2, '.', ''), '0'), '.').' KB';
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
