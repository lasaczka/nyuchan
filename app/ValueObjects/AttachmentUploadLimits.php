<?php

namespace App\ValueObjects;

class AttachmentUploadLimits
{
    private const int MIN_UPLOAD_KB = 1;
    private const int BYTES_IN_KB = 1024;
    private const int BYTES_IN_MB = 1024 * 1024;
    private const int BYTES_IN_GB = 1024 * 1024 * 1024;

    public function __construct(
        private readonly int $maxFiles,
        private readonly int $imageMaxKb,
        private readonly int $phpEffectiveMaxBytes,
    ) {
    }

    public static function fromRuntime(): self
    {
        $inputMaxBytes = (int) config('nyuchan.attachments_input_max_bytes', 8 * self::BYTES_IN_MB);
        $maxFiles = max(self::MIN_UPLOAD_KB, (int) config('nyuchan.attachments_max_files', 4));
        $imageMaxKb = max(self::MIN_UPLOAD_KB, (int) floor($inputMaxBytes / self::BYTES_IN_KB));

        $phpUploadMax = self::iniSizeToBytes((string) ini_get('upload_max_filesize'));
        $phpPostMax = self::iniSizeToBytes((string) ini_get('post_max_size'));
        $phpEffectiveMaxBytes = max(self::MIN_UPLOAD_KB, min($phpUploadMax, $phpPostMax));

        return new self($maxFiles, $imageMaxKb, $phpEffectiveMaxBytes);
    }

    public function maxFiles(): int
    {
        return $this->maxFiles;
    }

    public function imageMaxKb(): int
    {
        return $this->imageMaxKb;
    }

    public function imageMaxBytes(): int
    {
        return $this->imageMaxKb * self::BYTES_IN_KB;
    }

    public function phpEffectiveMaxBytes(): int
    {
        return $this->phpEffectiveMaxBytes;
    }

    public function imageMaxLabel(): string
    {
        return self::formatBytes($this->imageMaxBytes());
    }

    public function phpEffectiveMaxLabel(): string
    {
        return self::formatBytes($this->phpEffectiveMaxBytes);
    }

    private static function iniSizeToBytes(string $value): int
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return 0;
        }

        $unit = strtolower(substr($trimmed, -1));
        $number = (float) $trimmed;

        return (int) match ($unit) {
            'g' => $number * self::BYTES_IN_GB,
            'm' => $number * self::BYTES_IN_MB,
            'k' => $number * self::BYTES_IN_KB,
            default => (int) $number,
        };
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= self::BYTES_IN_MB) {
            return rtrim(rtrim(number_format($bytes / self::BYTES_IN_MB, 2, '.', ''), '0'), '.').' MB';
        }

        return rtrim(rtrim(number_format($bytes / self::BYTES_IN_KB, 2, '.', ''), '0'), '.').' KB';
    }
}

