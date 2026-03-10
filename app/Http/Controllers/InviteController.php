<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Invite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    private const int DEFAULT_USER_COOLDOWN_SECONDS = 3600;
    private const int DEFAULT_MOD_COOLDOWN_SECONDS = 600;

    public function create()
    {
        $user = Auth::user();
        $userId = $user?->id;
        if (! $userId) {
            abort(403);
        }

        $role = $user->role instanceof Role ? $user->role : Role::tryFrom((string) $user->role);
        $cooldownSeconds = $this->resolveCooldownSeconds($role);

        if ($cooldownSeconds !== null) {
            $recentInviteExists = Invite::query()
                ->where('created_by_user_id', $userId)
                ->where('created_at', '>', now()->subSeconds($cooldownSeconds))
                ->exists();

            if ($recentInviteExists) {
                return back()->withErrors([
                    'invite' => __('ui.invite_rate_limited_minutes', ['minutes' => (int) ($cooldownSeconds / 60)]),
                ]);
            }
        }

        $token = Str::random(32);

        $payload = [
            'token' => $token,
            'created_by_user_id' => $userId,
        ];

        if (Invite::supportsReusableColumns()) {
            $payload['max_uses'] = 1;
            $payload['uses_count'] = 0;
            $payload['is_active'] = true;
        }

        Invite::create($payload);

        return back()->with('invite', url('/register?invite='.$token));
    }

    private function resolveCooldownSeconds(?Role $role): ?int
    {
        $config = (array) config('nyuchan.invite_cooldown_seconds', []);

        return match ($role) {
            Role::Admin => $this->normalizeCooldown($config['admin'] ?? null),
            Role::Mod => $this->normalizeCooldown($config['mod'] ?? self::DEFAULT_MOD_COOLDOWN_SECONDS),
            default => $this->normalizeCooldown($config['user'] ?? self::DEFAULT_USER_COOLDOWN_SECONDS),
        };
    }

    private function normalizeCooldown(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $seconds = (int) $value;

        return $seconds > 0 ? $seconds : null;
    }
}
