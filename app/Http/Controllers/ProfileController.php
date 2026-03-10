<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Invite;
use App\Services\ThreadFavoritesService;
use App\Services\UserPostRepliesService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ThreadFavoritesService $threadFavorites,
        private readonly UserPostRepliesService $userPostReplies,
    ) {
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $activeTab = $request->query('tab') === 'settings' ? 'settings' : 'favorites';
        $favoritesPerPage = max(1, (int) config('nyuchan.pagination.profile_favorites_per_page', 50));
        $repliesPerPage = max(1, (int) config('nyuchan.pagination.profile_replies_per_page', 10));
        $role = $user->role instanceof Role ? $user->role : Role::tryFrom((string) $user->role);
        $inviteCooldownMinutes = match ($role) {
            Role::Admin => null,
            Role::Mod => 10,
            default => 60,
        };

        $activeOwnInvites = Invite::query()
            ->where('created_by_user_id', $user->id)
            ->active()
            ->latest('id');

        $lastUnusedInvite = $activeOwnInvites->first();

        $allActiveInvites = null;
        if ($role === Role::Admin) {
            $allActiveInvites = Invite::query()
                ->active()
                ->with(['creator:id,username'])
                ->latest('id')
                ->limit(300)
                ->get();
        }

        $favoriteThreadsAll = $this->threadFavorites->listFavoriteThreads($user, null);
        $favoritesPage = max(1, (int) $request->query('favorites_page', 1));
        $favoriteThreads = new LengthAwarePaginator(
            $favoriteThreadsAll->forPage($favoritesPage, $favoritesPerPage)->values(),
            $favoriteThreadsAll->count(),
            $favoritesPerPage,
            $favoritesPage,
            [
                'path' => route('profile.edit'),
                'pageName' => 'favorites_page',
            ]
        );
        $favoriteThreads->appends(['tab' => 'favorites']);

        $repliesToMyPosts = $this->userPostReplies->findRepliesForUser($user, 120);
        if ($activeTab === 'favorites' && $repliesToMyPosts->isNotEmpty()) {
            $latestReplyPostId = (int) max($repliesToMyPosts->pluck('reply_post_id')->all());
            if ((int) ($user->last_seen_reply_post_id ?? 0) < $latestReplyPostId) {
                $user->forceFill(['last_seen_reply_post_id' => $latestReplyPostId])->save();
            }
        }
        $repliesPage = max(1, (int) $request->query('replies_page', 1));
        $repliesPaginator = new LengthAwarePaginator(
            $repliesToMyPosts->forPage($repliesPage, $repliesPerPage)->values(),
            $repliesToMyPosts->count(),
            $repliesPerPage,
            $repliesPage,
            [
                'path' => route('profile.edit'),
                'pageName' => 'replies_page',
            ]
        );
        $repliesPaginator->appends(['tab' => 'favorites']);

        return view('profile.edit', [
            'user' => $user,
            'activeTab' => $activeTab,
            'profileColors' => config('nyuchan.profile_colors', []),
            'lastInviteUrl' => $lastUnusedInvite ? url('/register?invite='.$lastUnusedInvite->token) : null,
            'inviteCooldownMinutes' => $inviteCooldownMinutes,
            'allActiveInvites' => $allActiveInvites,
            'favoriteThreads' => $favoriteThreads,
            'repliesToMyPosts' => $repliesPaginator,
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

    public function markRepliesRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        $maxReplyPostId = max(0, (int) $request->integer('max_reply_post_id', 0));

        if ($maxReplyPostId > 0 && (int) ($user->last_seen_reply_post_id ?? 0) < $maxReplyPostId) {
            $user->forceFill(['last_seen_reply_post_id' => $maxReplyPostId])->save();
        }

        return Redirect::route('dashboard');
    }
}
