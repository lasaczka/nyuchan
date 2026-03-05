<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRecoveryKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RecoveryPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.recover-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'recovery_key' => ['required', 'string', 'max:128'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::query()->where('username', $validated['username'])->first();

        if (! $user) {
            return back()->withErrors(['username' => 'Unknown username.'])->withInput();
        }

        $newRecoveryKey = DB::transaction(function () use ($user, $validated) {
            $recovery = UserRecoveryKey::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $recovery || $recovery->used_at !== null) {
                return null;
            }

            $normalizedInput = UserRecoveryKey::normalize($validated['recovery_key']);

            if (! hash_equals($recovery->key_hash, hash('sha256', $normalizedInput))) {
                return false;
            }

            $user->update([
                'password' => $validated['password'],
            ]);

            $recovery->update([
                'used_at' => now(),
            ]);

            return UserRecoveryKey::issueFor($user);
        });

        if ($newRecoveryKey === false) {
            return back()->withErrors(['recovery_key' => 'Invalid recovery key.'])->withInput();
        }

        if ($newRecoveryKey === null) {
            return back()->withErrors(['recovery_key' => 'Recovery key is already used.'])->withInput();
        }

        return redirect()->route('recovery.key.show')
            ->with('status', 'Password updated. Save your new recovery key.')
            ->with('recovery_key', $newRecoveryKey);
    }
}
