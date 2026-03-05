<x-guest-layout>
    <div class="stack">
        <h2>{{ __('ui.password_recovery') }}</h2>
        <p class="muted">{{ __('ui.recovery_help') }}</p>

        <form method="POST" action="{{ route('recovery.store') }}" class="stack">
            @csrf

            <div>
                <label for="username">{{ __('ui.username') }}</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
                @error('username')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label for="recovery_key">{{ __('ui.recovery_key') }}</label>
                <input id="recovery_key" type="text" name="recovery_key" value="{{ old('recovery_key') }}" required>
                @error('recovery_key')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label for="password">{{ __('ui.new_password') }}</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label for="password_confirmation">{{ __('ui.confirm_password') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                @error('password_confirmation')<div class="error">{{ $message }}</div>@enderror
            </div>

            <button type="submit">{{ __('ui.reset_password') }}</button>
        </form>
    </div>
</x-guest-layout>
