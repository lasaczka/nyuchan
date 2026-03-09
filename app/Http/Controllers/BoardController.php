<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Thread;
use App\Services\PostDeleteReasonService;
use App\Services\PostFormatter;
use App\Services\QuoteLinkResolver;
use App\Services\ThreadFavoritesService;
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

    public function index(Board $board)
    {
        $favoriteThreadIds = auth()->check()
            ? $this->threadFavorites->listFavoriteThreadIds(auth()->user())
            : [];

        $threads = Thread::query()
            ->where('board_id', $board->id)
            ->orderByDesc('bumped_at')
            ->with(['posts' => function ($query) {
                $query->orderBy('id')->with('attachments');
            }])
            ->withCount('posts')
            ->paginate(20);

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

        return view('boards.index', compact('board', 'threads', 'favoriteThreadIds'));
    }
}
