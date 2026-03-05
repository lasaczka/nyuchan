<x-guest-layout>
    <div class="stack">
        <h2>{{ __('ui.login') }}</h2>

        <form method="POST" action="{{ route('login') }}" class="stack">
            @csrf

            <div>
                <label for="username">{{ __('ui.username') }}</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
                @error('username')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label for="password">{{ __('ui.password') }}</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>

            <label class="row" style="gap:.45rem; align-items:center; width:fit-content;">
                <input type="checkbox" name="remember" value="1" style="width:auto;">
                <span>{{ __('ui.remember_me') }}</span>
            </label>

            <div class="login-actions">
                <button type="submit" class="login-submit">{{ __('ui.login') }}</button>
                <a href="{{ route('recovery.create') }}" class="login-recover">{{ __('ui.recover_password') }}</a>
            </div>
        </form>
    </div>
</x-guest-layout>
