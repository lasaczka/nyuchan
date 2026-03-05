<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case User = 'user';
    case Mod = 'mod';

    public function canBan(): bool
    {
        return $this === self::Admin || $this === self::Mod;
    }

    public function canModerate(): bool
    {
        return $this === self::Admin || $this === self::Mod;
    }

    public function canManageRoles(): bool
    {
        return $this === self::Admin;
    }
}
