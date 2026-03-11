<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

class TripcodeService
{
    private const int TRIP_LENGTH = 10;
    private const string ALGORITHM_MODERN = 'modern';
    private const string ALGORITHM_TRADITIONAL = 'traditional';
    private const string TRADITIONAL_SALT_SUFFIX = 'H.';
    private const string TRADITIONAL_SALT_INVALID_PATTERN = '/[^\.-z]/';
    private const string TRADITIONAL_SALT_FROM = ':;<=>?@[\\]^_`';
    private const string TRADITIONAL_SALT_TO = 'ABCDEFGabcdef';

    public function generateForUser(User $user): ?string
    {
        if (! $user->use_tripcode) {
            return null;
        }

        $secret = (string) ($user->tripcode_secret ?? '');
        if ($secret === '') {
            return null;
        }

        $algorithm = strtolower((string) config('nyuchan.tripcodes.algorithm', self::ALGORITHM_MODERN));

        if ($algorithm === self::ALGORITHM_TRADITIONAL) {
            return $this->generateTraditional($secret);
        }

        return $this->generateModern($user->username, $secret);
    }

    private function generateModern(string $username, string $secret): string
    {
        $appKey = (string) config('app.key', '');
        if ($appKey === '') {
            throw new RuntimeException('APP_KEY is required for tripcode generation.');
        }

        $payload = $username.'|'.$secret;
        $hash = hash_hmac('sha256', $payload, $appKey, true);
        $encoded = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        return '!'.substr($encoded, 0, self::TRIP_LENGTH);
    }

    private function generateTraditional(string $secret): string
    {
        $saltSource = substr($secret.self::TRADITIONAL_SALT_SUFFIX, 1, 2);
        $salt = preg_replace(self::TRADITIONAL_SALT_INVALID_PATTERN, '.', $saltSource);
        $salt = strtr((string) $salt, self::TRADITIONAL_SALT_FROM, self::TRADITIONAL_SALT_TO);

        $hashed = crypt($secret, $salt);
        if (! is_string($hashed) || $hashed === '' || str_starts_with($hashed, '*')) {
            throw new RuntimeException('Traditional tripcode generation is not supported on this platform.');
        }

        return '!'.substr($hashed, -self::TRIP_LENGTH);
    }
}
