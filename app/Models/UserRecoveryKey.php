<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserRecoveryKey extends Model
{
    private const RAW_LENGTH = 32;

    protected $fillable = [
        'user_id',
        'key_hash',
        'issued_at',
        'used_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function issueFor(User $user): string
    {
        $raw = Str::upper(Str::random(self::RAW_LENGTH));
        $formatted = self::format($raw);

        static::updateOrCreate(
            ['user_id' => $user->id],
            [
                'key_hash' => hash('sha256', self::normalize($formatted)),
                'issued_at' => now(),
                'used_at' => null,
            ]
        );

        return $formatted;
    }

    public static function normalize(string $key): string
    {
        return Str::upper((string) preg_replace('/[^A-Za-z0-9]/', '', $key));
    }

    private static function format(string $raw): string
    {
        return implode('-', str_split($raw, 4));
    }
}
