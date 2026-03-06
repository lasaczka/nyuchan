@php
    $theme = session('theme', 'sugar');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Nyuchan') }}</title>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
</head>
<body>
@include('layouts.navigation')

<main class="container stack" style="padding: 1rem 0 2rem;">
    <div class="card">
        <h1>Nyuchan</h1>
        <p class="muted">{{ __('ui.closed_imageboard') }}</p>
    </div>

    <div class="card">
        <h2>{{ __('ui.board_stats') }}</h2>
        <table class="stats-table">
            <thead>
            <tr>
                <th>{{ __('ui.board') }}</th>
                <th>{{ __('ui.threads_cap') }}</th>
                <th>{{ __('ui.posts_cap') }}</th>
                <th>{{ __('ui.last_post') }}</th>
                <th>{{ __('ui.posts_24h') }}</th>
                <th>{{ __('ui.last_thread') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($boardStats as $stat)
                <tr>
                    <td>
                        @if(! empty($stat['is_hidden_aggregate']))
                            {{ $stat['title'] }}
                        @else
                            <a href="{{ route('boards.show', ['board' => $stat['slug']]) }}">/{{ $stat['slug'] }}/ - {{ $stat['title'] }}</a>
                        @endif
                    </td>
                    <td>{{ $stat['threads_count'] }}</td>
                    <td>{{ $stat['posts_count'] }}</td>
                    <td>
                        @if($stat['last_post_at'])
                            {{ \Carbon\Carbon::parse($stat['last_post_at'])->diffForHumans() }}
                        @else
                            {{ __('ui.never') }}
                        @endif
                    </td>
                    <td>{{ $stat['posts_last_24h'] }}</td>
                    <td>
                        @if(! empty($stat['is_hidden_aggregate']))
                            -
                        @elseif($stat['last_thread_id'])
                            <a href="{{ route('threads.show', ['board' => $stat['slug'], 'thread' => $stat['last_thread_id']]) }}">
                                #{{ $stat['last_thread_id'] }}@auth - {{ $stat['last_thread_title'] }}@endauth
                            </a>
                        @else
                            {{ __('ui.no_last_thread') }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('ui.no_boards') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</main>
</body>
</html>

