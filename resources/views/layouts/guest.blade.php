@php
    $theme = session('theme', 'sugar');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Nyuchan') }}</title>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
</head>
<body>
    @include('layouts.navigation')

    @if (session('status'))
        <div class="container" style="padding-top: 1rem;">
            <div class="notice">{{ session('status') }}</div>
        </div>
    @endif

    <main class="auth-box card stack">
        {{ $slot }}
    </main>
</body>
</html>
