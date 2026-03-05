<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'role',
        'profile_color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function recoveryKey(): HasOne
    {
        return $this->hasOne(UserRecoveryKey::class);
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
        ];
    }
}
