<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Invite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $userId = $user?->id;
        if (! $userId) {
            abort(403);
        }

        $role = $user->role instanceof Role ? $user->role : Role::tryFrom((string) $user->role);
        $cooldownSeconds = match ($role) {
            Role::Admin => null,
            Role::Mod => 10 * 60,
            default => 60 * 60,
        };

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

        Invite::create([
            'token' => $token,
            'created_by_user_id' => $userId,
        ]);

        return back()->with('invite', url('/register?invite='.$token));
    }
}
