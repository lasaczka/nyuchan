<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('ui.profile') }}</h2>
    </x-slot>

    <div class="stack">
        <div class="card stack">
            <h3>{{ __('ui.role') }}</h3>
            <p>{{ __('ui.role_'.($user->role->value ?? $user->role)) }}</p>
        </div>

        <div class="card stack">
            <h3>{{ __('ui.invites') }}</h3>
            @if($inviteCooldownMinutes === null)
                <p class="muted">{{ __('ui.invite_cooldown_admin') }}</p>
            @else
                <p class="muted">{{ __('ui.invite_cooldown_user', ['minutes' => $inviteCooldownMinutes]) }}</p>
            @endif
            <form method="POST" action="{{ route('invites.create') }}">
                @csrf
                <button type="submit">{{ __('ui.generate_invite') }}</button>
            </form>
            @error('invite')<div class="error">{{ $message }}</div>@enderror

            @if($lastInviteUrl)
                <div class="notice">
                    {{ __('ui.last_unused_invite') }}:
                    <a href="{{ $lastInviteUrl }}">{{ $lastInviteUrl }}</a>
                </div>
            @else
                <p class="muted">{{ __('ui.no_unused_invites') }}</p>
            @endif

            @if($allActiveInvites !== null)
                <h3>{{ __('ui.active_invites_all') }}</h3>
                @if($allActiveInvites->isEmpty())
                    <p class="muted">{{ __('ui.no_active_invites_all') }}</p>
                @else
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>{{ __('ui.invite_link') }}</th>
                                <th>{{ __('ui.mod_created_by') }}</th>
                                <th>{{ __('ui.mod_when') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allActiveInvites as $invite)
                                @php
                                    $inviteUrl = url('/register?invite='.$invite->token);
                                @endphp
                                <tr>
                                    <td>#{{ $invite->id }}</td>
                                    <td><a href="{{ $inviteUrl }}">{{ $inviteUrl }}</a></td>
                                    <td>{{ $invite->creator?->username ?? ('#'.$invite->created_by_user_id) }}</td>
                                    <td>{{ $invite->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endif
        </div>

        <div class="card stack">
            <h3>Username</h3>
            <form method="POST" action="{{ route('profile.update') }}" class="stack">
                @csrf
                @method('PATCH')
                <div>
                    <label for="username">Username</label>
                    <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}" required>
                    @error('username')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="profile_color">{{ __('ui.profile_name_color') }}</label>
                    <select id="profile_color" name="profile_color">
                        <option value="">{{ __('ui.color_default') }}</option>
                        @foreach($profileColors as $hex => $labelKey)
                            <option value="{{ $hex }}" {{ old('profile_color', $user->profile_color) === $hex ? 'selected' : '' }}>
                                {{ __($labelKey) }} ({{ $hex }})
                            </option>
                        @endforeach
                    </select>
                    @error('profile_color')<div class="error">{{ $message }}</div>@enderror
                </div>
                <button type="submit">Save</button>
            </form>
        </div>

        <div class="card stack">
            <h3>Change password</h3>
            <form method="POST" action="{{ route('password.update') }}" class="stack">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password">Current password</label>
                    <input id="current_password" type="password" name="current_password" required>
                    @if($errors->updatePassword->has('current_password'))<div class="error">{{ $errors->updatePassword->first('current_password') }}</div>@endif
                </div>
                <div>
                    <label for="password">New password</label>
                    <input id="password" type="password" name="password" required>
                    @if($errors->updatePassword->has('password'))<div class="error">{{ $errors->updatePassword->first('password') }}</div>@endif
                </div>
                <div>
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>
                <button type="submit">Update password</button>
            </form>
        </div>

        <div class="card stack">
            <h3>Delete account</h3>
            <form method="POST" action="{{ route('profile.destroy') }}" class="stack">
                @csrf
                @method('DELETE')
                <div>
                    <label for="delete_password">Password</label>
                    <input id="delete_password" type="password" name="password" required>
                    @if($errors->userDeletion->has('password'))<div class="error">{{ $errors->userDeletion->first('password') }}</div>@endif
                </div>
                <button type="submit" class="danger">Delete account</button>
            </form>
        </div>
    </div>
</x-app-layout>
