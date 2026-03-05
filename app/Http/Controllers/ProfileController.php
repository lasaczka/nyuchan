<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Invite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $role = $user->role instanceof Role ? $user->role : Role::tryFrom((string) $user->role);
        $inviteCooldownMinutes = match ($role) {
            Role::Admin => null,
            Role::Mod => 10,
            default => 60,
        };

        $activeOwnInvites = Invite::query()
            ->where('created_by_user_id', $user->id)
            ->whereNull('used_at')
            ->latest('id');

        $lastUnusedInvite = $activeOwnInvites->first();

        $allActiveInvites = null;
        if ($role === Role::Admin) {
            $allActiveInvites = Invite::query()
                ->whereNull('used_at')
                ->with(['creator:id,username'])
                ->latest('id')
                ->limit(300)
                ->get();
        }

        return view('profile.edit', [
            'user' => $user,
            'profileColors' => config('nyuchan.profile_colors', []),
            'lastInviteUrl' => $lastUnusedInvite ? url('/register?invite='.$lastUnusedInvite->token) : null,
            'inviteCooldownMinutes' => $inviteCooldownMinutes,
            'allActiveInvites' => $allActiveInvites,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
