<?php

namespace App\Services;

use App\Models\Post;

class QuoteLinkResolver
{
    public function buildQuoteLinks(array $postIds): array
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
}

