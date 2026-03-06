@php
    $theme = session('theme', 'sugar');
    $messages = trans('ui.error_404_pool');
    $messages = is_array($messages) && $messages !== [] ? $messages : ['Thread not found.'];
    $randomMessage = $messages[array_rand($messages)];
    $slugs = array_values(array_unique(array_merge(config('nyuchan.board_nav_order', ['a', 'b', 'rf', 'nsfw']), ['bb'])));
    $slugs = is_array($slugs) && $slugs !== [] ? $slugs : ['b'];
    $slug = $slugs[array_rand($slugs)];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - {{ config('app.name', 'Nyuchan') }}</title>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ @filemtime(public_path('css/site.css')) }}">
</head>
<body>
    <main class="container" style="padding: 3rem 0 2rem;">
        <section class="card error-page">
            <h1>404</h1>
            <h2>{{ __('ui.error_404_title') }}</h2>
            <p class="muted">{!! nl2br(e($randomMessage)) !!}</p>
            <p class="muted" style="margin-top:.8rem;">{{ __('ui.error_404_try') }}</p>
            <p><a class="button secondary" href="{{ url('/'.$slug) }}">/{{ $slug }}/</a></p>
            <p><a class="button secondary" href="{{ route('dashboard') }}">{{ __('ui.go_home') }}</a></p>
        </section>
    </main>
</body>
</html>
