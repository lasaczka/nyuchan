<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'role',
        'profile_color',
        'tripcode_secret',
        'use_tripcode',
        'show_name_with_tripcode',
        'last_seen_reply_post_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function recoveryKey(): HasOne
    {
        return $this->hasOne(UserRecoveryKey::class);
    }

    public function favoriteThreads(): BelongsToMany
    {
        return $this->belongsToMany(Thread::class, 'user_favorite_threads')->withTimestamps();
    }

    public function canModeratePosts(): bool
    {
        return ($this->role ?? Role::User)->canModerate();
    }

    public function canBanUsers(): bool
    {
        return ($this->role ?? Role::User)->canBan();
    }

    public function canManageRoles(): bool
    {
        return ($this->role ?? Role::User)->canManageRoles();
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'tripcode_secret' => 'encrypted',
            'use_tripcode' => 'boolean',
            'show_name_with_tripcode' => 'boolean',
        ];
    }
}
