<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Post;
use App\Models\Thread;
use App\Services\PostDeleteReasonService;
use App\Services\PostFormatter;
use App\Services\QuoteLinkResolver;
use App\Services\ThreadFavoritesService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BoardController extends Controller
{
    public function __construct(
        private readonly PostFormatter $formatter,
        private readonly QuoteLinkResolver $quoteLinkResolver,
        private readonly ThreadFavoritesService $threadFavorites,
        private readonly PostDeleteReasonService $deleteReasonService,
    ) {
    }

    public function index(Request $request, Board $board)
    {
        $searchQuery = trim((string) $request->query('q', ''));
        $escapedSearch = $this->escapeLike($searchQuery);
        $minSearchLength = max(1, (int) config('nyuchan.search.min_query_length', 2));
        $searchTooShort = $searchQuery !== '' && mb_strlen($searchQuery) < $minSearchLength;

        $favoriteThreadIds = auth()->check()
            ? $this->threadFavorites->listFavoriteThreadIds(auth()->user())
            : [];

        $threadsQuery = Thread::query()
            ->where('board_id', $board->id)
            ->with(['posts' => function ($query) {
                $query->orderBy('id')->with('attachments');
            }])
            ->withCount('posts');

        $threads = $threadsQuery
            ->orderByDesc('bumped_at')
            ->paginate(20)
            ->withQueryString();

        $searchTitleThreads = null;
        $searchPosts = null;
        if ($escapedSearch !== '' && ! $searchTooShort) {
            $searchTitleThreads = Thread::query()
                ->where('board_id', $board->id)
                ->where('title', 'like', '%'.$escapedSearch.'%')
                ->withCount('posts')
                ->orderByDesc('bumped_at')
                ->paginate(10, ['*'], 'titles_page')
                ->withQueryString();

            $searchPosts = Post::query()
                ->whereHas('thread', function ($query) use ($board) {
                    $query->where('board_id', $board->id);
                })
                ->where('body', 'like', '%'.$escapedSearch.'%')
                ->with(['thread:id,board_id,title', 'attachments'])
                ->orderByDesc('id')
                ->paginate(20, ['*'], 'posts_page')
                ->withQueryString();

            $searchPosts->getCollection()->transform(function (Post $post) use ($searchQuery) {
                $post->search_snippet = $this->makeSearchSnippet((string) $post->body, $searchQuery);

                return $post;
            });
        }

        $allPosts = $threads->getCollection()->flatMap(fn (Thread $thread) => $thread->posts);
        $bodies = $allPosts->pluck('body')->all();

        $quoteLinks = $this->quoteLinkResolver->buildQuoteLinks($this->formatter->extractQuoteIds($bodies));
        $deleteReasons = $this->deleteReasonService->loadForPosts($allPosts->where('is_deleted', true)->pluck('id')->all());

        $threads->getCollection()->each(function (Thread $thread) use ($quoteLinks, $deleteReasons): void {
            $thread->posts->each(function ($post) use ($quoteLinks, $thread, $deleteReasons): void {
                $post->delete_reason = $deleteReasons[$post->id] ?? null;

                if ($post->is_deleted) {
                    $post->rendered_preview = '';

                    return;
                }

                $post->rendered_preview = $this->formatter->format(
                    Str::limit($post->body, 220),
                    function (int $postId) use ($quoteLinks, $thread): ?array {
                        $target = $quoteLinks[$postId] ?? null;

                        if (! $target) {
                            return null;
                        }

                        return [
                            'href' => $target['href'],
                            'new_tab' => (int) $target['thread_id'] !== (int) $thread->id,
                        ];
                    }
                );
            });
        });

        return view('boards.index', compact(
            'board',
            'threads',
            'favoriteThreadIds',
            'searchQuery',
            'minSearchLength',
            'searchTooShort',
            'searchTitleThreads',
            'searchPosts'
        ));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }

    private function makeSearchSnippet(string $body, string $query): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $body) ?? '');
        if ($clean === '') {
            return '';
        }

        $query = trim($query);
        if ($query === '') {
            return e(Str::limit($clean, 220));
        }

        $position = mb_stripos($clean, $query);
        if ($position === false) {
            return e(Str::limit($clean, 220));
        }

        $radius = 90;
        $queryLength = max(1, mb_strlen($query));
        $start = max(0, $position - $radius);
        $length = min(mb_strlen($clean) - $start, $queryLength + ($radius * 2));
        $fragment = mb_substr($clean, $start, $length);

        if ($start > 0) {
            $fragment = '…'.$fragment;
        }
        if (($start + $length) < mb_strlen($clean)) {
            $fragment .= '…';
        }

        $escaped = e($fragment);
        $escapedQuery = e($query);
        if ($escapedQuery !== '') {
            $escaped = str_ireplace($escapedQuery, '<mark>'.$escapedQuery.'</mark>', $escaped);
        }

        return $escaped;
    }
}
