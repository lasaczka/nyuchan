<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use App\Models\UserRecoveryKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.register', [
            'inviteToken' => $request->query('invite'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'invite' => ['required', 'string', 'max:128'],
        ]);

        [$user, $recoveryKey] = DB::transaction(function () use ($validated) {
            $invite = Invite::query()
                ->where('token', $validated['invite'])
                ->lockForUpdate()
                ->first();

            if (! $invite || ! $invite->isRedeemable()) {
                throw ValidationException::withMessages([
                    'invite' => 'Invalid, expired, or exhausted invite code.',
                ]);
            }

            $user = User::create([
                'username' => $validated['username'],
                'password' => $validated['password'],
                'role' => Role::User,
            ]);

            $invite->consumeFor($user);

            $recoveryKey = UserRecoveryKey::issueFor($user);

            return [$user, $recoveryKey];
        });

        Auth::login($user);

        return redirect()->route('recovery.key.show')->with('recovery_key', $recoveryKey->value());
    }
}
