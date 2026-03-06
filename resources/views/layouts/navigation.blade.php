@php
    $theme = session('theme', 'sugar');
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
                    <option value="sugar" {{ $theme === 'sugar' ? 'selected' : '' }}>{{ __('ui.theme_sugar') }}</option>
                    <option value="makaba" {{ $theme === 'makaba' ? 'selected' : '' }}>{{ __('ui.theme_makaba') }}</option>
                    <option value="re-l" {{ $theme === 're-l' ? 'selected' : '' }}>{{ __('ui.theme_rel') }}</option>
                    <option value="nyu" {{ $theme === 'nyu' ? 'selected' : '' }}>{{ __('ui.theme_nyu') }}</option>
                    <option value="futaba" {{ $theme === 'futaba' ? 'selected' : '' }}>{{ __('ui.theme_futaba') }}</option>
                    <option value="yotsuba" {{ $theme === 'yotsuba' ? 'selected' : '' }}>{{ __('ui.theme_yotsuba') }}</option>
                    <option value="lelouch" {{ $theme === 'lelouch' ? 'selected' : '' }}>{{ __('ui.theme_lelouch') }}</option>
                </select>
            </label>

            <label class="row topbar-field">
                <span class="muted">{{ __('ui.language') }}</span>
                <select name="locale" class="topbar-select topbar-select-locale">
                    <option value="be" {{ $locale === 'be' ? 'selected' : '' }}>{{ __('ui.lang_be') }}</option>
                    <option value="ru" {{ $locale === 'ru' ? 'selected' : '' }}>{{ __('ui.lang_ru') }}</option>
                    <option value="en" {{ $locale === 'en' ? 'selected' : '' }}>{{ __('ui.lang_en') }}</option>
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
