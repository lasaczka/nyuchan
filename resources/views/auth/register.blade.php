<x-guest-layout>
    <div class="stack">
        <h2>Register</h2>

        <form method="POST" action="{{ route('register') }}" class="stack">
            @csrf

            <div>
                <label for="username">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
                @error('username')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                @error('password_confirmation')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label for="invite">Invite code</label>
                <input id="invite" type="text" name="invite" value="{{ old('invite', $inviteToken) }}" required>
                @error('invite')<div class="error">{{ $message }}</div>@enderror
            </div>

            <button type="submit">Register</button>
        </form>
    </div>
</x-guest-layout>
