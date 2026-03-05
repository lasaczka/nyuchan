<x-guest-layout>
    <div class="stack">
        <h2>{{ __('ui.save_recovery_key') }}</h2>
        <p class="muted">{{ __('ui.recovery_once') }}</p>

        <div class="card panel">
            <label for="rk">{{ __('ui.recovery_key') }}</label>
            <input id="rk" type="text" readonly value="{{ $recoveryKey }}">
        </div>

        <form method="POST" action="{{ route('recovery.key.ack') }}" class="stack">
            @csrf
            <label>
                <input type="checkbox" name="saved" value="1" required>
                {{ __('ui.confirm_saved_key') }}
            </label>
            @error('saved')<div class="error">{{ $message }}</div>@enderror
            <button type="submit">{{ __('ui.continue') }}</button>
        </form>
    </div>
</x-guest-layout>
