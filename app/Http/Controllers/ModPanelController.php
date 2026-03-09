<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Ban;
use App\Models\ModAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ModPanelController extends Controller
{
    private const array AVAILABLE_TABS = [
        'tools',
        'users',
        'announcements',
        'bans',
        'log',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->canModeratePosts(), 403);
        $requestedTab = (string) $request->query('tab', 'tools');
        $activeTab = in_array($requestedTab, self::AVAILABLE_TABS, true) ? $requestedTab : 'tools';

        $users = User::query()->orderBy('username')->get(['id', 'username', 'role']);

        $activeBans = Ban::query()
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->limit(200)
            ->get();

        $actions = ModAction::query()
            ->latest('id')
            ->limit(300)
            ->get();

        $actorIds = $actions->pluck('actor_user_id')
            ->merge($activeBans->pluck('created_by_user_id'))
            ->filter()
            ->unique()
            ->values();

        $actorNames = User::query()
            ->whereIn('id', $actorIds)
            ->pluck('username', 'id');

        $announcements = collect();
        if (Schema::hasTable('announcements')) {
            $announcementsPerPage = max(1, (int) config('nyuchan.pagination.mod_announcements_per_page', 5));
            $announcements = Announcement::query()
                ->with('creator:id,username')
                ->latest('published_at')
                ->latest('id')
                ->paginate($announcementsPerPage, ['*'], 'announcements_page')
                ->withQueryString();
        }

        return view('mod.index', [
            'users' => $users,
            'activeBans' => $activeBans,
            'actions' => $actions,
            'actorNames' => $actorNames,
            'roles' => [Role::User->value, Role::Mod->value, Role::Admin->value],
            'announcements' => $announcements,
            'activeTab' => $activeTab,
        ]);
    }

    public function updateUserRole(Request $request, User $targetUser)
    {
        $actor = $request->user();
        abort_unless($actor && $actor->canManageRoles(), 403);

        $data = $request->validate([
            'role' => ['required', Rule::in([Role::User->value, Role::Mod->value, Role::Admin->value])],
        ]);

        if ($actor->id === $targetUser->id && $data['role'] !== Role::Admin->value) {
            return back()->with('status', __('ui.mod_self_demotion_blocked'));
        }

        $targetUser->update(['role' => $data['role']]);

        ModAction::create([
            'actor_user_id' => $actor->id,
            'action' => 'update_user_role',
            'target_type' => User::class,
            'target_id' => $targetUser->id,
            'reason' => 'role='.$data['role'],
        ]);

        return back()->with('status', __('ui.mod_role_updated'));
    }

    public function unban(Request $request, Ban $ban)
    {
        $actor = $request->user();
        abort_unless($actor && $actor->canBanUsers(), 403);

        $banId = $ban->id;
        $ban->delete();

        ModAction::create([
            'actor_user_id' => $actor->id,
            'action' => 'unban',
            'target_type' => Ban::class,
            'target_id' => $banId,
            'reason' => 'manual unban',
        ]);

        return back()->with('status', __('ui.mod_ban_removed'));
    }

    public function toggleUi(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor && $actor->canModeratePosts(), 403);

        $enabled = (string) $request->input('show_mod_tools', '1') !== '0';
        $request->session()->put('show_mod_tools', $enabled);

        return back()->with('status', __('ui.mod_tools_updated'));
    }

    public function storeAnnouncement(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor && $actor->canModeratePosts(), 403);

        if (! Schema::hasTable('announcements')) {
            return back()->with('status', __('ui.announcements_table_missing'));
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
            'is_published' => ['nullable', 'boolean'],
            'show_author' => ['nullable', 'boolean'],
        ]);

        $publish = (bool) ($data['is_published'] ?? false);
        $showAuthor = (bool) ($data['show_author'] ?? false);

        Announcement::create([
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'is_published' => $publish,
            'published_at' => $publish ? now() : null,
            'created_by_user_id' => $actor->id,
            'show_author' => $showAuthor,
        ]);

        return back()->with('status', __('ui.announcement_saved'));
    }

    public function publishAnnouncement(Request $request, Announcement $announcement)
    {
        $actor = $request->user();
        abort_unless($actor && $actor->canModeratePosts(), 403);

        if (! Schema::hasTable('announcements')) {
            return back()->with('status', __('ui.announcements_table_missing'));
        }

        if (! $announcement->is_published) {
            $announcement->update([
                'is_published' => true,
                'published_at' => now(),
            ]);
        }

        return back()->with('status', __('ui.announcement_published'));
    }
}
