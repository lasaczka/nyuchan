<?php

namespace App\Http\Controllers;

use App\Models\PostAttachment;
use App\Services\AttachmentStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(private readonly AttachmentStorage $attachments)
    {
    }

    public function show(Request $request, PostAttachment $attachment, ?string $filename = null): StreamedResponse
    {
        $variant = (string) $request->query('variant', 'original');

        $path = $variant === 'thumb' && $attachment->thumb_path
            ? $attachment->thumb_path
            : $attachment->path;

        $stream = $this->attachments->readStream($path);
        abort_unless(is_resource($stream), 404);
        $contentType = $variant === 'thumb'
            ? 'image/jpeg'
            : ($attachment->mime ?: 'application/octet-stream');

        // Prevent any previously buffered output from corrupting binary image payload.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->stream(
            function () use ($stream): void {
                fpassthru($stream);
                fclose($stream);
            },
            200,
            [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'public, max-age=604800',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
