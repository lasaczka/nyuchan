<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

class TripcodeService
{
    private const int TRIP_LENGTH = 10;

    public function generateForUser(User $user): ?string
    {
        if (! $user->use_tripcode) {
            return null;
        }

        $secret = (string) ($user->tripcode_secret ?? '');
        if ($secret === '') {
            return null;
        }

        $appKey = (string) config('app.key', '');
        if ($appKey === '') {
            throw new RuntimeException('APP_KEY is required for tripcode generation.');
        }

        $payload = $user->username.'|'.$secret;
        $hash = hash_hmac('sha256', $payload, $appKey, true);
        $encoded = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        return '!'.substr($encoded, 0, self::TRIP_LENGTH);
    }
}

