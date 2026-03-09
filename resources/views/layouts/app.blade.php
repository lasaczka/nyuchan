@php
    $theme = session('theme', \App\Enums\SiteTheme::default()->value);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Nyuchan') }}</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="icon" href="{{ asset('favicon/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ @filemtime(public_path('css/site.css')) }}">
</head>
<body>
    @include('layouts.navigation')

    @isset($header)
        <div class="container" style="padding-top: 1rem;">
            {{ $header }}
        </div>
    @endisset

    @if (session('invite'))
        <div class="container" style="padding-top: 1rem;">
            <div class="notice">
                {{ __('ui.invite_link') }}: <a href="{{ session('invite') }}">{{ session('invite') }}</a>
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="container" style="padding-top: 1rem;">
            <div class="notice">{{ session('status') }}</div>
        </div>
    @endif

    <main class="container" style="padding: 1rem 0 2rem;">
        {{ $slot }}
    </main>
</body>
</html>


