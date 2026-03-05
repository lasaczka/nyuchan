<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\ModAction;
use App\Models\Post;
use App\Models\Thread;
use App\Support\PostFormatter;
use Illuminate\Support\Str;

class BoardController extends Controller
{
    public function index(Board $board)
    {
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

        $quoteLinks = $this->buildQuoteLinks(PostFormatter::extractQuoteIds($bodies));
        $deleteReasons = $this->loadDeleteReasons($allPosts->where('is_deleted', true)->pluck('id')->all());

        $threads->getCollection()->each(function (Thread $thread) use ($quoteLinks, $deleteReasons): void {
            $thread->posts->each(function ($post) use ($quoteLinks, $thread, $deleteReasons): void {
                $post->delete_reason = $deleteReasons[$post->id] ?? null;

                if ($post->is_deleted) {
                    $post->rendered_preview = '';

                    return;
                }

                $post->rendered_preview = PostFormatter::format(
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

        return view('boards.index', compact('board', 'threads'));
    }

    private function buildQuoteLinks(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        return Post::query()
            ->whereIn('id', $postIds)
            ->with(['thread.board'])
            ->get()
            ->mapWithKeys(function (Post $post): array {
                $boardSlug = $post->thread?->board?->slug;
                $threadId = $post->thread?->id;

                if (! $boardSlug || ! $threadId) {
                    return [];
                }

                return [
                    $post->id => [
                        'href' => route('threads.show', ['board' => $boardSlug, 'thread' => $threadId]).'#p'.$post->id,
                        'thread_id' => (int) $threadId,
                    ],
                ];
            })
            ->all();
    }

    private function loadDeleteReasons(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        return ModAction::query()
            ->where('action', 'delete_post')
            ->where('target_type', Post::class)
            ->whereIn('target_id', $postIds)
            ->orderByDesc('id')
            ->get()
            ->unique('target_id')
            ->mapWithKeys(function (ModAction $action): array {
                $reason = trim((string) $action->reason);

                if ($reason === '' || $reason === 'no reason') {
                    $reason = null;
                }

                return [(int) $action->target_id => $reason];
            })
            ->all();
    }
}
