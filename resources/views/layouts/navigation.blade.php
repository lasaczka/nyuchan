@php
    $theme = session('theme', \App\Enums\SiteTheme::default()->value);
    $locale = app()->getLocale();
@endphp
<nav class="topbar">
    <div class="container topbar-inner">
        <div class="row topbar-left">
            <a href="{{ route('dashboard') }}" class="brand-logo">Nyuchan</a>
            <a href="{{ route('rules') }}">{{ __('ui.rules') }}</a>
            @auth
                <a href="{{ route('profile.edit') }}">{{ __('ui.profile') }}</a>
                @if(auth()->user()?->canModeratePosts())
                    <a href="{{ route('mod.index') }}">{{ __('ui.mod') }}</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="topbar-logout" style="display:inline;">
                    @csrf
                    <button type="submit" class="secondary">{{ __('ui.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}">{{ __('ui.login') }}</a>
                <a href="{{ route('register') }}">{{ __('ui.register') }}</a>
            @endauth
        </div>

        <form method="GET" action="{{ route('locale.set') }}" class="row topbar-controls">
            <label class="row topbar-field">
                <span class="muted">{{ __('ui.theme') }}</span>
                <select name="theme" class="topbar-select topbar-select-theme">
                    @foreach(\App\Enums\SiteTheme::cases() as $themeOption)
                        <option value="{{ $themeOption->value }}" {{ $theme === $themeOption->value ? 'selected' : '' }}>
                            {{ __($themeOption->labelKey()) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="row topbar-field">
                <span class="muted">{{ __('ui.language') }}</span>
                <select name="locale" class="topbar-select topbar-select-locale">
                    @foreach(\App\Enums\SiteLocale::cases() as $localeOption)
                        <option value="{{ $localeOption->value }}" {{ $locale === $localeOption->value ? 'selected' : '' }}>
                            {{ __($localeOption->labelKey()) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="secondary topbar-apply">{{ __('ui.apply') }}</button>
        </form>
    </div>
    @if(isset($navBoards) && $navBoards->isNotEmpty())
        <div class="container board-nav">
            @foreach($navBoards as $navBoard)
                <a href="{{ route('boards.show', ['board' => $navBoard->slug]) }}">/{{ $navBoard->slug }}/</a>@if(! $loop->last) <span class="muted">•</span> @endif
            @endforeach
        </div>
    @endif
</nav>
