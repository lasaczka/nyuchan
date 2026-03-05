<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('ui.mod_panel') }}</h2>
    </x-slot>

    <div class="stack">
        @php
            $showModTools = session('show_mod_tools', true);
        @endphp
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
        </div>

        <div class="card">
            <h3>{{ __('ui.mod_users_roles') }}</h3>
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

        <div class="card">
            <h3>{{ __('ui.mod_active_bans') }}</h3>
            @if($activeBans->isEmpty())
                <p class="muted">{{ __('ui.mod_no_active_bans') }}</p>
            @else
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
            @endif
        </div>

        <div class="card">
            <h3>{{ __('ui.mod_log') }}</h3>
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
</x-app-layout>
