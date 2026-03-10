<?php

namespace App\Models;

use App\ValueObjects\RecoveryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRecoveryKey extends Model
{
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

    public static function issueFor(User $user): RecoveryKey
    {
        $key = RecoveryKey::generate();

        static::updateOrCreate(
            ['user_id' => $user->id],
            [
                'key_hash' => $key->hash(),
                'issued_at' => now(),
                'used_at' => null,
            ]
        );

        return $key;
    }
}
