<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentStorage
{
    public function disk(): string
    {
        return (string) config('nyuchan.attachments_disk', config('filesystems.default', 'local'));
    }

    public function readStream(string $path)
    {
        $primaryDisk = $this->disk();

        try {
            $stream = Storage::disk($primaryDisk)->readStream($path);
            if (is_resource($stream)) {
                return $stream;
            }
        } catch (\Throwable) {
            // Fall through to fallback disks.
        }

        foreach (config('nyuchan.attachments_fallback_disks', []) as $fallbackDisk) {
            $fallbackDisk = (string) $fallbackDisk;
            if ($fallbackDisk === '' || $fallbackDisk === $primaryDisk) {
                continue;
            }

            try {
                $stream = Storage::disk($fallbackDisk)->readStream($path);
                if (is_resource($stream)) {
                    return $stream;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return false;
    }

    public function storeUploadedFile(UploadedFile $file): array
    {
        $disk = $this->disk();
        $inputMax = max(1, (int) config('nyuchan.attachments_input_max_bytes', 8 * 1024 * 1024));
        $targetMax = max(1, (int) config('nyuchan.attachments_target_max_bytes', 1024 * 1024));

        $sourcePath = (string) $file->getRealPath();
        $sourceSize = (int) ($file->getSize() ?: File::size($sourcePath));
        if ($sourceSize > $inputMax) {
            throw ValidationException::withMessages([
                'image' => __('ui.image_too_large_input', ['size' => $this->formatBytes($inputMax)]),
            ]);
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $pathForStorage = $sourcePath;
        $tempPath = null;

        if ($sourceSize > $targetMax) {
            if (! in_array($mime, config('nyuchan.attachments_auto_compress_mimes', []), true)) {
                if ($mime === 'image/gif') {
                    throw ValidationException::withMessages([
                        'image' => __('ui.image_gif_too_large', ['size' => $this->formatBytes($targetMax)]),
                    ]);
                }

                throw ValidationException::withMessages([
                    'image' => __('ui.image_too_large_target', ['size' => $this->formatBytes($targetMax)]),
                ]);
            }

            $compressedPath = $this->compressToJpegPath($sourcePath, $mime, $targetMax);
            if (! $compressedPath) {
                throw ValidationException::withMessages([
                    'image' => __('ui.image_too_large_target', ['size' => $this->formatBytes($targetMax)]),
                ]);
            }

            $tempPath = $compressedPath;
            $pathForStorage = $compressedPath;
            $mime = 'image/jpeg';
        }

        $extension = $this->extensionFromMime($mime);
        $storedPath = 'attachments/'.Str::uuid().'.'.$extension;
        $stream = fopen($pathForStorage, 'rb');
        if (! is_resource($stream)) {
            throw new \RuntimeException('Unable to read uploaded file stream.');
        }

        $written = Storage::disk($disk)->put($storedPath, $stream);
        fclose($stream);
        if ($written !== true) {
            throw new \RuntimeException('Attachment upload failed on disk ['.$disk.'] at path ['.$storedPath.'].');
        }

        $size = (int) (File::size($pathForStorage) ?: 0);
        $dimensions = @getimagesize($pathForStorage);

        [$thumbBinary, $thumbWidth, $thumbHeight] = $this->generateThumbBinary($pathForStorage, $mime);
        $thumbPath = null;

        if ($thumbBinary !== null) {
            $thumbPath = 'attachments/thumbs/'.Str::uuid().'.jpg';
            $thumbWritten = Storage::disk($disk)->put($thumbPath, $thumbBinary);
            if ($thumbWritten !== true) {
                $thumbPath = null;
                $thumbWidth = null;
                $thumbHeight = null;
            }
        }

        if ($tempPath && is_file($tempPath)) {
            @unlink($tempPath);
        }

        return [
            'path' => $storedPath,
            'thumb_path' => $thumbPath,
            'mime' => $mime,
            'size' => $size,
            'width' => $dimensions !== false ? $dimensions[0] : null,
            'height' => $dimensions !== false ? $dimensions[1] : null,
            'thumb_width' => $thumbWidth,
            'thumb_height' => $thumbHeight,
            'original_name' => $file->getClientOriginalName(),
        ];
    }

    private function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    private function generateThumbBinary(string $sourcePath, string $mime): array
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagecopyresampled') || ! function_exists('imagejpeg')) {
            return [null, null, null];
        }

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return [null, null, null];
        }

        $src = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($sourcePath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : false,
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($sourcePath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (! $src) {
            return [null, null, null];
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($src);

            return [null, null, null];
        }

        $ratio = min(320 / $srcW, 320 / $srcH, 1);
        $dstW = max(1, (int) floor($srcW * $ratio));
        $dstH = max(1, (int) floor($srcH * $ratio));

        $dst = imagecreatetruecolor($dstW, $dstH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        ob_start();
        $ok = @imagejpeg($dst, null, 85);
        $binary = ob_get_clean();

        imagedestroy($dst);
        imagedestroy($src);

        if (! $ok || ! is_string($binary) || $binary === '') {
            return [null, null, null];
        }

        return [$binary, $dstW, $dstH];
    }

    private function compressToJpegPath(string $sourcePath, string $mime, int $targetMax): ?string
    {
        if (! function_exists('imagejpeg')) {
            return null;
        }

        $src = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($sourcePath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (! $src) {
            return null;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($src);

            return null;
        }

        $maxSide = 2200;
        $ratio = min($maxSide / $srcW, $maxSide / $srcH, 1);
        $dstW = max(1, (int) floor($srcW * $ratio));
        $dstH = max(1, (int) floor($srcH * $ratio));
        $dst = imagecreatetruecolor($dstW, $dstH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        $tmp = tempnam(sys_get_temp_dir(), 'nyu_img_');
        if (! is_string($tmp) || $tmp === '') {
            imagedestroy($dst);
            imagedestroy($src);

            return null;
        }

        $ok = false;
        foreach ([86, 80, 74, 68, 62, 56, 50] as $quality) {
            $ok = @imagejpeg($dst, $tmp, $quality);
            if (! $ok) {
                continue;
            }

            if ((int) File::size($tmp) <= $targetMax) {
                break;
            }
        }

        imagedestroy($dst);
        imagedestroy($src);

        if (! $ok || (int) File::size($tmp) > $targetMax) {
            @unlink($tmp);

            return null;
        }

        return $tmp;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / (1024 * 1024), 2, '.', ''), '0'), '.').' MB';
        }

        return rtrim(rtrim(number_format($bytes / 1024, 2, '.', ''), '0'), '.').' KB';
    }
}
