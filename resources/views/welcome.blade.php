@php
    $theme = session('theme', \App\Enums\SiteTheme::default()->value);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Nyuchan') }}</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="icon" href="{{ asset('favicon/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ @filemtime(public_path('css/site.css')) }}">
</head>
<body>
@include('layouts.navigation')

<main class="container stack" style="padding: 1rem 0 2rem;">
    <div class="card">
        <h1>Nyuchan</h1>
        <p class="muted">{{ __('ui.closed_imageboard') }}</p>
    </div>
    @auth
        @if(!empty($replyNotice) && ($replyNotice['count'] ?? 0) > 0)
            <div class="card">
                <h2>{{ __('ui.new_replies_title') }} ({{ (int) $replyNotice['count'] }})</h2>
                <div class="row" style="gap:.5rem; margin-top:.5rem;">
                    <a class="button" href="{{ route('profile.edit', ['tab' => 'favorites']) }}#replies">{{ __('ui.new_replies_open') }}</a>
                    <form method="POST" action="{{ route('replies.mark_read') }}">
                        @csrf
                        <input type="hidden" name="max_reply_post_id" value="{{ $replyNotice['latest_reply_post_id'] ?? 0 }}">
                        <button type="submit" class="secondary">{{ __('ui.new_replies_mark_read') }}</button>
                    </form>
                </div>
            </div>
        @endif
    @endauth

    <div class="card">
        <h2>{{ __('ui.board_stats') }}</h2>
        <div class="table-scroll">
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
    </div>

    <div class="card">
        <h2>{{ __('ui.announcements') }}</h2>
        @if(isset($announcements) && $announcements->isNotEmpty())
            <ul class="list">
                @foreach($announcements as $announcement)
                    <li>
                        <strong>{{ $announcement->title }}</strong>
                        <div class="muted">{{ $announcement->published_at?->diffForHumans() ?? __('ui.just_now') }}</div>
                        @if($announcement->show_author && $announcement->creator)
                            <div class="muted">{{ __('ui.announcement_by', ['username' => $announcement->creator->username]) }}</div>
                        @endif
                        <div class="post-body" style="margin-top:.35rem;">{!! $announcement->rendered_body ?? e((string) $announcement->body) !!}</div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="muted">{{ __('ui.no_announcements') }}</p>
        @endif
    </div>

    <div class="card">
        <h2>{{ __('ui.feedback_title') }}</h2>
        @if($feedbackThread)
            @php
                $feedbackTitle = in_array($feedbackThread->title, ['Bugs and suggestions', 'meta-feedback-thread', 'fixing-nyuch-thread'], true)
                    ? __('ui.feedback_thread_title')
                    : $feedbackThread->title;
            @endphp
            <a href="{{ route('threads.show', ['board' => 'meta', 'thread' => $feedbackThread->id]) }}">
                /meta/{{ $feedbackThread->id }} - {{ $feedbackTitle }}
            </a>
            <p class="muted" style="margin-top:.5rem;">{{ __('ui.feedback_hint') }}</p>
        @else
            <p class="muted">{{ __('ui.feedback_missing') }}</p>
        @endif
    </div>
</main>
</body>
</html>



