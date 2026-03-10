<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('ui.mod_panel') }}</h2>
    </x-slot>

    <div class="stack">
        @php
            $showModTools = session('show_mod_tools', true);
            $tabs = [
                'tools' => __('ui.mod_tab_tools'),
                'users' => __('ui.mod_tab_users'),
                'announcements' => __('ui.mod_tab_announcements'),
                'bans' => __('ui.mod_tab_bans'),
                'log' => __('ui.mod_tab_log'),
            ];
        @endphp
        <div class="card mod-tabs">
            <div class="row wrap" style="gap:.5rem;">
                @foreach($tabs as $tabKey => $tabLabel)
                    <a
                        href="{{ route('mod.index', ['tab' => $tabKey]) }}"
                        class="button secondary tab-link {{ ($activeTab ?? 'tools') === $tabKey ? 'is-active' : '' }}"
                    >{{ $tabLabel }}</a>
                @endforeach
            </div>
        </div>

        @if(($activeTab ?? 'tools') === 'tools')
            <div class="card">
                <h3>{{ __('ui.mod_tools') }}</h3>
                <form method="POST" action="{{ route('mod.tools.toggle') }}" class="mod-inline mod-inline-combined">
                    @csrf
                    <select name="show_mod_tools" style="width:auto; min-width:10rem;">
                        <option value="1" {{ $showModTools ? 'selected' : '' }}>{{ __('ui.mod_tools_on') }}</option>
                        <option value="0" {{ ! $showModTools ? 'selected' : '' }}>{{ __('ui.mod_tools_off') }}</option>
                    </select>
                    <button type="submit">{{ __('ui.save_settings') }}</button>
                </form>

                @if(auth()->user()?->canManageRoles())
                    <hr>
                    <h4>{{ __('ui.reusable_invites') }}</h4>
                    @if($reusableInvitesAvailable ?? false)
                        <form method="POST" action="{{ route('mod.invites.reusable.store') }}" class="mod-inline mod-inline-combined reusable-invite-form">
                            @csrf
                            <input type="number" name="max_uses" min="0" max="100000" placeholder="{{ __('ui.reusable_invite_max_uses') }}" class="reusable-invite-input">
                            <input type="number" name="expires_in_minutes" min="1" max="525600" placeholder="{{ __('ui.reusable_invite_expires_minutes') }}" class="reusable-invite-input">
                            <button type="submit">{{ __('ui.reusable_invite_create_short') }}</button>
                        </form>
                    @else
                        <p class="muted">{{ __('ui.reusable_invites_unavailable') }}</p>
                    @endif

                    @if(($activeReusableInvites ?? collect())->isEmpty())
                        <p class="muted">{{ __('ui.reusable_invites_empty') }}</p>
                    @else
                        <div class="table-scroll" style="margin-top:.65rem;">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>{{ __('ui.invite_link') }}</th>
                                        <th>{{ __('ui.mod_created_by') }}</th>
                                        <th>{{ __('ui.reusable_invite_uses') }}</th>
                                        <th>{{ __('ui.mod_until') }}</th>
                                        <th>{{ __('ui.mod_actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeReusableInvites as $invite)
                                        @php
                                            $inviteUrl = url('/register?invite='.$invite->token);
                                            $usesLabel = $invite->max_uses
                                                ? ($invite->uses_count.' / '.$invite->max_uses)
                                                : ($invite->uses_count.' / ∞');
                                        @endphp
                                        <tr>
                                            <td>#{{ $invite->id }}</td>
                                            <td><a href="{{ $inviteUrl }}">{{ $inviteUrl }}</a></td>
                                            <td>{{ $invite->creator?->username ?? ('#'.$invite->created_by_user_id) }}</td>
                                            <td>{{ $usesLabel }}</td>
                                            <td>{{ $invite->expires_at ? $invite->expires_at->format('Y-m-d H:i') : __('ui.never') }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('mod.invites.reusable.revoke', ['invite' => $invite->id]) }}">
                                                    @csrf
                                                    <button type="submit" class="secondary">{{ __('ui.reusable_invite_revoke') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        @endif

        @if(($activeTab ?? 'tools') === 'users')
            <div class="card">
                <h3>{{ __('ui.mod_users_roles') }}</h3>
                <p class="muted">{{ __('ui.mod_users_count', ['count' => $users->total()]) }}</p>
                <div class="table-scroll">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>{{ __('ui.mod_username') }}</th>
                            <th>{{ __('ui.mod_role') }}</th>
                            <th>{{ __('ui.mod_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                            @php
                                $roleValue = $u->role->value ?? $u->role;
                            @endphp
                            <tr>
                                <td>{{ $u->username }}</td>
                                <td>{{ __('ui.role_'.$roleValue) }}</td>
                                <td>
                                    @if(auth()->user()?->canManageRoles())
                                        <form method="POST" action="{{ route('mod.users.role', ['targetUser' => $u->id]) }}" class="mod-inline mod-inline-combined">
                                            @csrf
                                            <select name="role" style="width:auto; min-width:8rem;">
                                                @foreach($roles as $role)
                                                    <option value="{{ $role }}" {{ $roleValue === $role ? 'selected' : '' }}>{{ __('ui.role_'.$role) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit">{{ __('ui.save_settings') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                @if($users->hasPages())
                    <div style="margin-top:.75rem;">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        @endif

        @if(($activeTab ?? 'tools') === 'announcements')
            <div class="card">
                <h3>{{ __('ui.announcements_manage') }}</h3>

                @if(isset($announcements) && $announcements->isNotEmpty())
                    <h4>{{ __('ui.announcements_recent') }}</h4>
                    <ul class="list">
                        @foreach($announcements as $ann)
                            <li>
                                <strong>{{ $ann->title }}</strong>
                                <span class="muted">
                                    · {{ $ann->is_published ? __('ui.announcement_status_published') : __('ui.announcement_status_draft') }}
                                    @if($ann->published_at)
                                        · {{ $ann->published_at->format('Y-m-d H:i') }}
                                    @endif
                                    @if($ann->show_author && $ann->creator)
                                        · {{ __('ui.announcement_by', ['username' => $ann->creator->username]) }}
                                    @endif
                                </span>
                                @if(! $ann->is_published)
                                    <form method="POST" action="{{ route('mod.announcements.publish', ['announcement' => $ann->id]) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="secondary">{{ __('ui.announcement_publish') }}</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if(method_exists($announcements, 'hasPages') && $announcements->hasPages())
                        <div style="margin-top:.75rem;">
                            {{ $announcements->links() }}
                        </div>
                    @endif
                    <hr>
                @endif

                <form method="POST" action="{{ route('mod.announcements.store') }}" class="stack">
                    @csrf
                    <label for="ann-title">{{ __('ui.announcement_title') }}</label>
                    <input id="ann-title" name="title" type="text" required maxlength="180">

                    <label for="ann-body">{{ __('ui.announcement_body') }}</label>
                    <textarea id="ann-body" name="body" rows="5" required></textarea>

                    <label style="display:flex; gap:.5rem; align-items:center; width:max-content;">
                        <input type="checkbox" name="is_published" value="1">
                        <span>{{ __('ui.announcement_publish_now') }}</span>
                    </label>
                    <label style="display:flex; gap:.5rem; align-items:center; width:max-content;">
                        <input type="checkbox" name="show_author" value="1">
                        <span>{{ __('ui.announcement_show_author') }}</span>
                    </label>

                    <button type="submit">{{ __('ui.announcement_create') }}</button>
                </form>
            </div>
        @endif

        @if(($activeTab ?? 'tools') === 'bans')
            <div class="card">
                <h3>{{ __('ui.mod_active_bans') }}</h3>
                @if($activeBans->isEmpty())
                    <p class="muted">{{ __('ui.mod_no_active_bans') }}</p>
                @else
                    <div class="table-scroll">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Abuse ID</th>
                                <th>{{ __('ui.mod_reason') }}</th>
                                <th>{{ __('ui.mod_until') }}</th>
                                <th>{{ __('ui.mod_created_by') }}</th>
                                <th>{{ __('ui.mod_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeBans as $ban)
                                <tr>
                                    <td>#{{ $ban->id }}</td>
                                    <td>{{ $ban->abuse_id }}</td>
                                    <td>{{ $ban->reason }}</td>
                                    <td>{{ $ban->expires_at ? $ban->expires_at->format('Y-m-d H:i') : 'permanent' }}</td>
                                    <td>{{ $actorNames[$ban->created_by_user_id] ?? ('#'.$ban->created_by_user_id) }}</td>
                                    <td>
                                        @if(auth()->user()?->canBanUsers())
                                            <form method="POST" action="{{ route('mod.bans.unban', ['ban' => $ban->id]) }}">
                                                @csrf
                                                <button type="submit" class="secondary">{{ __('ui.mod_unban') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        @endif

        @if(($activeTab ?? 'tools') === 'log')
            <div class="card">
                <h3>{{ __('ui.mod_log') }}</h3>
                <div class="table-scroll">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('ui.mod_when') }}</th>
                            <th>{{ __('ui.mod_actor') }}</th>
                            <th>{{ __('ui.mod_action') }}</th>
                            <th>{{ __('ui.mod_target') }}</th>
                            <th>{{ __('ui.mod_reason') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($actions as $action)
                            <tr>
                                <td>#{{ $action->id }}</td>
                                <td>{{ $action->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $actorNames[$action->actor_user_id] ?? ('#'.$action->actor_user_id) }}</td>
                                <td>{{ $action->action }}</td>
                                <td>{{ class_basename($action->target_type) }} #{{ $action->target_id }}</td>
                                <td>{{ $action->reason }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
