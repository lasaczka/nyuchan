<?php

namespace App\ValueObjects;

use InvalidArgumentException;
use RuntimeException;

final class AbuseId
{
    private function __construct(private readonly string $value)
    {
    }

    public static function fromUserId(?int $userId): self
    {
        $normalizedId = max(0, (int) ($userId ?? 0));
        $key = self::hmacKey();
        $hash = hash_hmac('sha256', (string) $normalizedId, $key);

        return new self($hash);
    }

    public static function fromStored(string $value): self
    {
        $normalized = strtolower(trim($value));
        if (! preg_match('/^[a-f0-9]{64}$/', $normalized)) {
            throw new InvalidArgumentException('Invalid abuse id format.');
        }

        return new self($normalized);
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function hmacKey(): string
    {
        $appKey = (string) config('app.key', '');
        if ($appKey === '') {
            throw new RuntimeException('APP_KEY is missing. Cannot derive abuse_id.');
        }

        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }

            throw new RuntimeException('APP_KEY has invalid base64 format.');
        }

        return $appKey;
    }
}
