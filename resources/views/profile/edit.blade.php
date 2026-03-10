<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('ui.profile') }}</h2>
    </x-slot>

    <div class="stack">
        <div class="card mod-tabs">
            <div class="row wrap" style="gap:.5rem;">
                <a class="button secondary tab-link {{ $activeTab === 'favorites' ? 'is-active' : '' }}" href="{{ route('profile.edit', ['tab' => 'favorites']) }}">{{ __('ui.profile_tab_favorites') }}</a>
                <a class="button secondary tab-link {{ $activeTab === 'settings' ? 'is-active' : '' }}" href="{{ route('profile.edit', ['tab' => 'settings']) }}">{{ __('ui.profile_tab_settings') }}</a>
            </div>
        </div>

        @if($activeTab === 'favorites')
            <div class="card stack">
                <h3>{{ __('ui.favorite_threads') }}</h3>
                @if($favoriteThreads->isEmpty())
                    <p class="muted">{{ __('ui.no_favorite_threads') }}</p>
                @else
                    <div class="{{ $favoriteThreads->total() > 10 ? 'favorites-scroll' : '' }}">
                    <ul class="list">
                        @foreach($favoriteThreads as $thread)
                            <li>
                                <a href="{{ route('threads.show', ['board' => $thread->board?->slug, 'thread' => $thread->id]) }}">
                                    /{{ $thread->board?->slug }}/{{ $thread->id }} - {{ $thread->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    </div>
                    @if($favoriteThreads->hasPages())
                        <div style="margin-top:.75rem;">
                            {{ $favoriteThreads->links() }}
                        </div>
                    @endif
                @endif
            </div>

            <div class="card stack" id="replies">
                <h3>{{ __('ui.replies_to_my_posts') }}</h3>
                @if($repliesToMyPosts->isEmpty())
                    <p class="muted">{{ __('ui.no_replies_to_my_posts') }}</p>
                @else
                    <ul class="list">
                        @foreach($repliesToMyPosts as $item)
                            <li>
                                <div>
                                    <a href="{{ route('threads.show', ['board' => $item['reply_board_slug'], 'thread' => $item['reply_thread_id']]) }}#p{{ $item['reply_post_id'] }}">#{{ $item['reply_post_id'] }}</a>
                                    {{ __('ui.replied_to') }}
                                    <a href="{{ route('threads.show', ['board' => $item['target_board_slug'], 'thread' => $item['target_thread_id']]) }}#p{{ $item['target_post_id'] }}">#{{ $item['target_post_id'] }}</a>
                                </div>
                                @if($item['reply_post_created_at'])
                                    <div class="muted">{{ $item['reply_post_created_at']->diffForHumans() }}</div>
                                @endif
                                <div class="post-body" style="margin-top:.35rem;">{!! $item['reply_post_preview'] !!}</div>
                            </li>
                        @endforeach
                    </ul>
                    @if($repliesToMyPosts->hasPages())
                        <div style="margin-top:.75rem;">
                            {{ $repliesToMyPosts->links() }}
                        </div>
                    @endif
                @endif
            </div>
        @else
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
                        <div class="table-scroll">
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
                        </div>
                    @endif
                @endif
            </div>

            <div class="card stack">
                <h3>{{ __('ui.username') }}</h3>
                <form method="POST" action="{{ route('profile.update') }}" class="stack">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="username">{{ __('ui.username') }}</label>
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
                    <div>
                        <label class="row" style="gap:.45rem; align-items:center;">
                            <input type="checkbox" name="use_tripcode" value="1" style="width:auto;" {{ old('use_tripcode', $user->use_tripcode) ? 'checked' : '' }}>
                            <span>{{ __('ui.use_tripcode') }}</span>
                        </label>
                        @error('use_tripcode')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="row" style="gap:.45rem; align-items:center;">
                            <input type="checkbox" name="show_name_with_tripcode" value="1" style="width:auto;" {{ old('show_name_with_tripcode', $user->show_name_with_tripcode) ? 'checked' : '' }}>
                            <span>{{ __('ui.show_name_with_tripcode') }}</span>
                        </label>
                        @error('show_name_with_tripcode')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="tripcode_secret">{{ __('ui.tripcode_secret') }}</label>
                        <input id="tripcode_secret" type="password" name="tripcode_secret" autocomplete="new-password" placeholder="{{ __('ui.tripcode_secret_placeholder') }}">
                        @error('tripcode_secret')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit">{{ __('ui.save') }}</button>
                </form>
            </div>

            <div class="card stack">
                <h3>{{ __('ui.change_password') }}</h3>
                <form method="POST" action="{{ route('password.update') }}" class="stack">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="current_password">{{ __('ui.current_password') }}</label>
                        <input id="current_password" type="password" name="current_password" required>
                        @if($errors->updatePassword->has('current_password'))<div class="error">{{ $errors->updatePassword->first('current_password') }}</div>@endif
                    </div>
                    <div>
                        <label for="password">{{ __('ui.new_password') }}</label>
                        <input id="password" type="password" name="password" required>
                        @if($errors->updatePassword->has('password'))<div class="error">{{ $errors->updatePassword->first('password') }}</div>@endif
                    </div>
                    <div>
                        <label for="password_confirmation">{{ __('ui.confirm_password') }}</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required>
                    </div>
                    <button type="submit">{{ __('ui.update_password') }}</button>
                </form>
            </div>

            <div class="card stack">
                <h3>{{ __('ui.delete_account') }}</h3>
                <form method="POST" action="{{ route('profile.destroy') }}" class="stack">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="delete_password">{{ __('ui.password') }}</label>
                        <input id="delete_password" type="password" name="password" required>
                        @if($errors->userDeletion->has('password'))<div class="error">{{ $errors->userDeletion->first('password') }}</div>@endif
                    </div>
                    <button type="submit" class="danger">{{ __('ui.delete_account') }}</button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
