<x-app-layout>
    @php
        $showModTools = session('show_mod_tools', true);
    @endphp
    <x-slot name="header">
        <h2>/{{ $board->slug }}/ - {{ $board->display_title }}</h2>
    </x-slot>

    <div class="stack">
        <div class="card panel">
            <details class="reply-details board-search-details" @if(!empty($searchQuery)) open @endif>
                <summary class="board-search-summary">
                    <span class="muted board-description board-search-label">{{ $board->display_description }}</span>
                    <span
                        class="button secondary board-search-trigger"
                        title="{{ __('ui.search_in_board') }}"
                        aria-label="{{ __('ui.search_in_board') }}"
                    ><span class="sr-only">{{ __('ui.search_in_board') }}</span></span>
                </summary>
                <form method="GET" action="{{ route('boards.show', ['board' => $board->slug]) }}" class="row wrap board-search-form">
                    <input id="board-search" type="search" name="q" value="{{ $searchQuery ?? '' }}" placeholder="{{ __('ui.search_placeholder') }}" minlength="{{ $minSearchLength ?? 2 }}">
                    <button type="submit">{{ __('ui.search') }}</button>
                    @if(!empty($searchQuery))
                        <a class="button secondary" href="{{ route('boards.show', ['board' => $board->slug]) }}">{{ __('ui.search_reset') }}</a>
                    @endif
                </form>
                @if(!empty($searchQuery) && empty($searchTooShort))
                    <p class="muted board-search-caption">{{ __('ui.search_results_for', ['q' => $searchQuery]) }}</p>
                @elseif(!empty($searchTooShort))
                    <p class="muted board-search-caption">{{ __('ui.search_min_length', ['count' => $minSearchLength ?? 2]) }}</p>
                @endif
            </details>
        </div>

        @if(auth()->user()?->canBanUsers() && $showModTools)
            <div class="card">
                <h3>{{ __('ui.board_settings') }}</h3>
                <form method="POST" action="{{ route('mod.board.settings', ['board' => $board->slug]) }}" class="grid-2">
                    @csrf
                    <div>
                        <label for="thread_limit">{{ __('ui.thread_limit') }}</label>
                        <input id="thread_limit" name="thread_limit" type="number" min="10" max="5000" value="{{ old('thread_limit', (int) ($board->thread_limit ?? 100)) }}" required>
                    </div>
                    <div>
                        <label for="bump_limit">{{ __('ui.bump_limit') }}</label>
                        <input id="bump_limit" name="bump_limit" type="number" min="10" max="1000" value="{{ old('bump_limit', (int) $board->bump_limit) }}" required>
                    </div>
                    <div>
                        <label for="post_rate_limit_count">{{ __('ui.rate_limit_count') }}</label>
                        <input id="post_rate_limit_count" name="post_rate_limit_count" type="number" min="1" max="30" value="{{ old('post_rate_limit_count', (int) ($board->post_rate_limit_count ?? 3)) }}" required>
                    </div>
                    <div>
                        <label for="post_rate_limit_window_seconds">{{ __('ui.rate_limit_window_seconds') }}</label>
                        <input id="post_rate_limit_window_seconds" name="post_rate_limit_window_seconds" type="number" min="5" max="3600" value="{{ old('post_rate_limit_window_seconds', (int) ($board->post_rate_limit_window_seconds ?? 60)) }}" required>
                    </div>
                    <div class="row" style="align-items:flex-end;">
                        <button type="submit">{{ __('ui.save_settings') }}</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="card" id="thread-form">
            <details class="reply-details" @if($errors->any()) open @endif>
                <summary class="button create-thread-summary">{{ __('ui.thread_create_go_form') }}</summary>
                <form method="POST" action="{{ route('threads.store', ['board' => $board->slug]) }}" class="stack" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label for="title">{{ __('ui.thread_title') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="140" required>
                        @error('title')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="row" style="gap:.45rem; align-items:center;">
                            <input type="checkbox" name="use_display_name" value="1" style="width:auto;" {{ old('use_display_name') ? 'checked' : '' }}>
                            <span>{{ __('ui.post_with_name') }}</span>
                        </label>
                        @error('use_display_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="images">{{ __('ui.image_optional') }}</label>
                        <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                        <label class="row" style="gap:.45rem; align-items:center; margin-top:.45rem;">
                            <input type="checkbox" name="strip_metadata" value="1" style="width:auto;" {{ old('strip_metadata') ? 'checked' : '' }}>
                            <span>{{ __('ui.strip_metadata') }}</span>
                        </label>
                        <div class="muted format-help">{{ __('ui.image_policy') }}</div>
                        @error('images')<div class="error">{{ $message }}</div>@enderror
                        @if($errors->has('images.*'))<div class="error">{{ $errors->first('images.*') }}</div>@endif
                    </div>
                    <div>
                        <label for="body">{{ __('ui.message') }}</label>
                        <textarea id="body" name="body" required>{{ old('body') }}</textarea>
                        <div class="muted format-help">{{ __('ui.format_help') }}</div>
                        @error('body')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit">{{ __('ui.post_send') }}</button>
                </form>
            </details>
        </div>

        @if(!empty($searchQuery) && empty($searchTooShort))
            <div class="card stack">
                <h3>{{ __('ui.search_threads_title') }}</h3>
                @if(($searchTitleThreads?->total() ?? 0) === 0)
                    <p class="muted">{{ __('ui.search_no_results') }}</p>
                @else
                    <ul class="list">
                        @foreach($searchTitleThreads as $thread)
                            <li>
                                <a href="{{ route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]) }}">
                                    /{{ $board->slug }}/{{ $thread->id }} — {{ $thread->title }}
                                </a>
                                <span class="muted"> · {{ $thread->posts_count }} {{ __('ui.posts') }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if($searchTitleThreads->hasPages())
                        <div style="margin-top:.75rem;">
                            {{ $searchTitleThreads->links() }}
                        </div>
                    @endif
                @endif
            </div>

            <div class="card stack">
                <h3>{{ __('ui.search_posts_title') }}</h3>
                @if(($searchPosts?->total() ?? 0) === 0)
                    <p class="muted">{{ __('ui.search_no_results') }}</p>
                @else
                    <ul class="list preview-list">
                        @foreach($searchPosts as $post)
                            <li>
                                <div class="post-meta">
                                    <a class="post-no" href="{{ route('threads.show', ['board' => $board->slug, 'thread' => $post->thread_id]) }}#p{{ $post->id }}">#{{ $post->id }}</a>
                                    <span>{{ __('ui.search_in_thread') }}</span>
                                    <a href="{{ route('threads.show', ['board' => $board->slug, 'thread' => $post->thread_id]) }}">{{ $post->thread?->title ?: ('#'.$post->thread_id) }}</a>
                                </div>
                                <div class="post-body" style="margin-top:.35rem;">{!! $post->search_snippet !!}</div>
                            </li>
                        @endforeach
                    </ul>
                    @if($searchPosts->hasPages())
                        <div style="margin-top:.75rem;">
                            {{ $searchPosts->links() }}
                        </div>
                    @endif
                @endif
            </div>
        @else
        <div class="stack">
            @forelse($threads as $thread)
                @php
                    $opPost = $thread->posts->first();
                    $lastReplies = $thread->posts->slice(1)->take(-3)->values();
                    $previewPosts = collect();
                    $threadIsFavorite = in_array($thread->id, $favoriteThreadIds ?? [], true);

                    if ($opPost) {
                        $previewPosts->push($opPost);
                    }

                    $previewPosts = $previewPosts->merge($lastReplies)->unique('id')->values();
                @endphp
                <article class="card thread-card stack">
                    <div class="row mobile-col" style="justify-content: space-between;">
                        <a href="{{ route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]) }}"><strong>{{ $thread->title }}</strong></a>
                    </div>
                    <div class="muted">{{ __('ui.thread_num') }}{{ $thread->id }} • {{ $thread->posts_count }} {{ __('ui.posts') }}</div>

                    @if($previewPosts->isEmpty())
                        <p class="muted">{{ __('ui.no_posts_in_thread') }}</p>
                    @else
                        <ul class="list preview-list">
                            @foreach($previewPosts as $post)
                                <li class="thread-post {{ $opPost && $post->id === $opPost->id ? 'op-preview' : '' }} {{ $post->is_deleted ? 'mod-deleted' : '' }}" id="p{{ $post->id }}">
                                    <div class="post-meta">
                                        <a class="post-no" href="{{ route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]) }}#p{{ $post->id }}">#{{ $post->id }}</a>
                                        <span>{{ __('ui.by') }}</span>
                                        @if($post->tripcode && ! $post->display_name)
                                            <span class="post-identity"><span class="post-author">{{ $board->display_anonymous_name }}</span><span class="post-tripcode" @if($post->display_color) style="color: {{ $post->display_color }};" @endif>{{ $post->tripcode }}</span></span>
                                        @else
                                            <span class="post-author" @if($post->display_color) style="color: {{ $post->display_color }};" @endif>{{ $post->display_name ?: $board->display_anonymous_name }}@if($post->tripcode){{ $post->tripcode }}@endif</span>
                                        @endif
                                        @if($post->created_at)
                                            @php
                                                $postTimeLabel = mb_convert_case($post->created_at->locale(app()->getLocale())->isoFormat('DD/MM/YY ddd HH:mm:ss'), MB_CASE_TITLE, 'UTF-8');
                                            @endphp
                                            <time class="post-time" datetime="{{ $post->created_at->toIso8601String() }}" title="{{ $post->created_at->format('Y-m-d H:i:s T') }}">{{ $postTimeLabel }}</time>
                                        @endif
                                        @if($opPost && $post->id === $opPost->id)
                                            <span>({{ __('ui.op_short') }})</span>
                                        @endif
                                        @if($opPost && $post->id === $opPost->id)
                                            @auth
                                                <form method="POST" action="{{ route('threads.favorite.toggle', ['board' => $board->slug, 'thread' => $thread->id]) }}" class="post-fav-form">
                                                    @csrf
                                                    <button type="submit" class="post-fav {{ $threadIsFavorite ? 'is-active' : '' }}" title="{{ $threadIsFavorite ? __('ui.unfavorite_thread') : __('ui.favorite_thread') }}">{{ $threadIsFavorite ? '★' : '☆' }}</button>
                                                </form>
                                            @endauth
                                        @endif
                                        <a class="post-reply" href="{{ route('threads.show', ['board' => $board->slug, 'thread' => $thread->id, 'quote' => $post->id]) }}#post-form" title="{{ __('ui.reply') }}">➤</a>
                                        @if($post->is_sage)
                                            <span class="post-sage">{{ __('ui.sage') }}</span>
                                        @endif
                                    </div>
                                    <div class="thread-post-layout">
                                        @if($post->attachments->isNotEmpty() && ! $post->is_deleted)
                                            @php
                                                $attachmentsCount = min($post->attachments->count(), 4);
                                            @endphp
                                            <div class="attachments-grid attachments-count-{{ $attachmentsCount }}">
                                                @foreach($post->attachments as $attachment)
                                                    @php
                                                        $fileName = $attachment->original_name ?: ('image-'.$attachment->id.'.jpg');
                                                    @endphp
                                                    <div class="attachment-block">
                                                        <a href="{{ route('media.file', ['attachment' => $attachment->id, 'filename' => $fileName]) }}" target="_blank" rel="noopener" class="attachment-link" title="{{ $attachment->original_name ?: 'image' }}">
                                                            <img class="post-image" src="{{ route('media.file', ['attachment' => $attachment->id, 'filename' => $fileName, 'variant' => 'thumb']) }}" alt="{{ $attachment->original_name ?: 'image' }}" loading="lazy">
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="post-main">
                                            @if($post->is_deleted)
                                                <div class="post-body mod-deleted-note">{{ __('ui.deleted_post') }}</div>
                                                @if($post->delete_reason)
                                                    <div class="post-body muted deleted-reason">{{ __('ui.deleted_reason', ['reason' => $post->delete_reason]) }}</div>
                                                @endif
                                            @else
                                                <div class="post-body">{!! $post->rendered_preview !!}</div>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="row wrap thread-card-actions">
                        <a class="button secondary" href="{{ route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]) }}">{{ __('ui.open_thread') }}</a>

                        @if(auth()->user()?->canModeratePosts() && $showModTools)
                            <form method="POST" action="{{ route('mod.thread.delete', ['board' => $board->slug, 'thread' => $thread->id]) }}" class="mod-inline mod-inline-combined">
                                @csrf
                                <input type="text" name="reason" placeholder="{{ __('ui.reason_optional') }}">
                                @if(auth()->user()?->canBanUsers())
                                    <input type="number" name="minutes" min="5" max="43200" value="60" required>
                                    <button type="submit" class="danger">{{ __('ui.delete_short') }}</button>
                                    <button type="submit" class="danger" formaction="{{ route('mod.thread.ban_author', ['board' => $board->slug, 'thread' => $thread->id]) }}">{{ __('ui.ban_short') }}</button>
                                @else
                                    <button type="submit" class="danger">{{ __('ui.delete_short') }}</button>
                                @endif
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="card">
                    <p>{{ __('ui.no_threads') }}</p>
                </div>
            @endforelse
        </div>
        @endif

        @if(empty($searchQuery) && $threads->count() > 1)
            <div class="card panel" style="text-align:center;">
                <a class="button secondary" href="#thread-form">{{ __('ui.to_top') }}</a>
            </div>
        @endif

        @if(empty($searchQuery))
            <div>
                {{ $threads->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
