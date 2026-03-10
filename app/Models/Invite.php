<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Invite extends Model
{
    private const array REUSABLE_COLUMNS = ['max_uses', 'uses_count', 'expires_at', 'is_active'];

    protected $fillable = [
        'token',
        'max_uses',
        'uses_count',
        'created_by_user_id',
        'used_at',
        'expires_at',
        'is_active',
        'used_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive($query)
    {
        if (! self::supportsReusableColumns()) {
            return $query->whereNull('used_at');
        }

        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->where('max_uses', 0)
                    ->orWhereColumn('uses_count', '<', 'max_uses')
                    ->orWhere(function ($nested) {
                        $nested->whereNull('max_uses')->where('uses_count', '<', 1);
                    });
            });
    }

    public function isRedeemable(): bool
    {
        if (! self::supportsReusableColumns()) {
            return $this->used_at === null;
        }

        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        $maxUses = $this->resolvedMaxUses();

        if ($maxUses !== null && (int) $this->uses_count >= $maxUses) {
            return false;
        }

        return true;
    }

    public function consumeFor(User $user): void
    {
        if (! self::supportsReusableColumns()) {
            $this->forceFill([
                'used_at' => now(),
                'used_by_user_id' => $user->id,
            ])->save();

            return;
        }

        $nextUses = (int) $this->uses_count + 1;
        $maxUses = $this->resolvedMaxUses();
        $isExhausted = $maxUses !== null && $nextUses >= $maxUses;

        $this->forceFill([
            'uses_count' => $nextUses,
            'used_at' => now(),
            'used_by_user_id' => $maxUses === 1 ? $user->id : $this->used_by_user_id,
            'is_active' => $isExhausted ? false : (bool) $this->is_active,
        ])->save();
    }

    private function resolvedMaxUses(): ?int
    {
        $rawMaxUses = $this->max_uses;
        if ($rawMaxUses === 0) {
            return null;
        }

        if ($rawMaxUses === null) {
            return 1;
        }

        return max(1, (int) $rawMaxUses);
    }

    public static function supportsReusableColumns(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumns('invites', self::REUSABLE_COLUMNS);
        }

        return $supports;
    }
}
