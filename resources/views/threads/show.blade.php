<x-app-layout>
    @php
        /** @var \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $posts */
        $posts = $posts ?? collect();
        $showModTools = session('show_mod_tools', true);
    @endphp
    <x-slot name="header">
        <h2>/{{ $board->slug }}/ {{ $thread->title }}</h2>
    </x-slot>

    <div class="stack">
        @if(auth()->user()?->canModeratePosts() && $showModTools)
            <div class="card mod-thread-actions">
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
            </div>
        @endif

        <div class="card thread-form-card" id="post-form">
            <a class="button secondary thread-down-button" href="#thread-bottom" title="{{ __('ui.to_bottom') }}">
                <span class="thread-down-label-full">{{ __('ui.to_bottom') }}</span>
                <span class="thread-down-label-mobile" aria-hidden="true">↓</span>
            </a>
            <details class="reply-details thread-reply-details" @if($errors->any() || $quotePostId) open @endif>
                <summary class="button">{{ __('ui.post_reply_go_form') }}</summary>
                <form method="POST" action="{{ route('posts.store', ['board' => $board->slug, 'thread' => $thread->id]) }}" class="stack" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label class="row" style="gap:.45rem; align-items:center;">
                            <input type="checkbox" name="use_display_name" value="1" style="width:auto;" {{ old('use_display_name') ? 'checked' : '' }}>
                            <span>{{ __('ui.post_with_name') }}</span>
                        </label>
                        @error('use_display_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="row" style="gap:.45rem; align-items:center;">
                            <input type="checkbox" name="sage" value="1" style="width:auto;" {{ old('sage') ? 'checked' : '' }}>
                            <span>{{ __('ui.sage') }}</span>
                        </label>
                        @error('sage')<div class="error">{{ $message }}</div>@enderror
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
                        <textarea id="body" name="body" required>{{ old('body', $quotePostId ? ('>>'.$quotePostId."\n") : '') }}</textarea>
                        <div class="muted format-help">{{ __('ui.format_help') }}</div>
                        @error('body')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit">{{ __('ui.post_send') }}</button>
                </form>
            </details>
        </div>

        <div class="card">
            @php
                $postsCount = $posts->count();
                $locale = app()->getLocale();

                if (in_array($locale, ['ru', 'be'], true)) {
                    $n = $postsCount % 100;
                    $n1 = $postsCount % 10;

                    if ($n > 10 && $n < 20) {
                        $postWord = __('ui.post_many');
                    } elseif ($n1 === 1) {
                        $postWord = __('ui.post_one');
                    } elseif ($n1 >= 2 && $n1 <= 4) {
                        $postWord = __('ui.post_few');
                    } else {
                        $postWord = __('ui.post_many');
                    }
                } else {
                    $postWord = $postsCount === 1 ? __('ui.post_one') : __('ui.post_many');
                }
            @endphp
            <h3>{{ $postsCount }} {{ $postWord }}</h3>
            <ul class="list">
                @foreach($posts as $post)
                    <li id="p{{ $post->id }}" class="thread-post {{ $loop->first ? 'op-preview' : '' }} {{ $post->is_deleted ? 'mod-deleted' : '' }}">
                        <div class="post-meta">
                            <a class="post-no" href="#p{{ $post->id }}">#{{ $post->id }}</a>
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
                            @if($post->is_op_in_thread ?? false)
                                <span>({{ __('ui.op_short') }})</span>
                            @endif
                            @if($loop->first)
                                @auth
                                    <form method="POST" action="{{ route('threads.favorite.toggle', ['board' => $board->slug, 'thread' => $thread->id]) }}" class="post-fav-form">
                                        @csrf
                                        <button type="submit" class="post-fav {{ $isFavorite ? 'is-active' : '' }}" title="{{ $isFavorite ? __('ui.unfavorite_thread') : __('ui.favorite_thread') }}">{{ $isFavorite ? '★' : '☆' }}</button>
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
                                            <a class="attachment-link" href="{{ route('media.file', ['attachment' => $attachment->id, 'filename' => $fileName]) }}" target="_blank" rel="noopener" title="{{ $attachment->original_name ?: 'image' }}">
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
                                    <div class="post-body">{!! $post->rendered_body !!}</div>
                                @endif

                                @if(auth()->user()?->canModeratePosts() && $showModTools)
                                    <div class="mod-actions mod-actions-inline">
                                        @if(! $post->is_deleted)
                                            <form method="POST" action="{{ route('mod.post.delete', ['board' => $board->slug, 'thread' => $thread->id, 'post' => $post->id]) }}" class="mod-inline mod-inline-combined">
                                                @csrf
                                                <input type="text" name="reason" placeholder="{{ __('ui.reason_optional') }}">
                                                @if(auth()->user()?->canBanUsers())
                                                    <input type="number" name="minutes" min="5" max="43200" value="60" required>
                                                    <button type="submit" class="danger">{{ __('ui.delete_short') }}</button>
                                                    <button type="submit" class="danger" formaction="{{ route('mod.post.ban', ['board' => $board->slug, 'thread' => $thread->id, 'post' => $post->id]) }}">{{ __('ui.ban_short') }}</button>
                                                @else
                                                    <button type="submit" class="danger">{{ __('ui.delete_short') }}</button>
                                                @endif
                                            </form>
                                        @elseif(auth()->user()?->canBanUsers())
                                            <form method="POST" action="{{ route('mod.post.ban', ['board' => $board->slug, 'thread' => $thread->id, 'post' => $post->id]) }}" class="mod-inline mod-inline-combined">
                                                @csrf
                                                <input type="text" name="reason" placeholder="{{ __('ui.ban_reason') }}" required>
                                                <input type="number" name="minutes" min="5" max="43200" value="60" required>
                                                <button type="submit" class="danger">{{ __('ui.ban_short') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        @if($posts->count() > 1)
            <div id="thread-bottom"></div>
            <div class="card panel" style="text-align:center;">
                <a class="button secondary" href="#post-form">{{ __('ui.to_top') }}</a>
            </div>
        @endif
    </div>
</x-app-layout>
