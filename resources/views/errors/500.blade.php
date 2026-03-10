@php
    $theme = session('theme', \App\Enums\SiteTheme::default()->value);
    $messages = trans('ui.error_500_pool');
    $messages = is_array($messages) && $messages !== [] ? $messages : ['Server error.'];
    $randomMessage = $messages[array_rand($messages)];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - {{ config('app.name', 'Nyuchan') }}</title>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ config('app.version') }}">
</head>
<body>
    <main class="container" style="padding: 3rem 0 2rem;">
        <section class="card error-page">
            <h1>500</h1>
            <h2>{{ __('ui.error_500_title') }}</h2>
            <p class="muted">{!! nl2br(e($randomMessage)) !!}</p>
            <p><a class="button secondary" href="{{ route('dashboard') }}">{{ __('ui.go_home') }}</a></p>
        </section>
    </main>
</body>
</html>


