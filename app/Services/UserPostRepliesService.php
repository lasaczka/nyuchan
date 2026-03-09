<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Support\PostingGuard;
use App\ValueObjects\AbuseId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

readonly class UserPostRepliesService
{
    private const int USER_POST_SCAN_LIMIT = 1200;
    private const int CANDIDATE_SCAN_LIMIT = 2500;
    private const int PREVIEW_LENGTH = 240;

    public function __construct(
        private PostFormatter $formatter,
        private QuoteLinkResolver $quoteLinkResolver,
    ) {
    }

    public function findRepliesForUser(User $user, int $limit = 120): Collection
    {
        if (! Schema::hasTable('post_metas')) {
            return collect();
        }

        $myPosts = $this->findUserPosts($user);
        $myPostIds = $this->extractPostIds($myPosts);

        if ($myPostIds === []) {
            return collect();
        }

        $myPostLookup = $myPosts->keyBy('id');
        $candidates = $this->findCandidateReplies($myPostIds);
        $quoteLinks = $this->buildQuoteLinksForCandidates($candidates);
        $rows = $this->buildReplyRows($candidates, $myPostIds, $myPostLookup, $quoteLinks);

        return $rows
            ->unique(fn (array $row): string => $row['reply_post_id'].':'.$row['target_post_id'])
            ->sortByDesc('reply_post_id')
            ->take($limit)
            ->values();
    }

    public function summarizeNewRepliesForUser(User $user, int $afterReplyPostId = 0): array
    {
        if (! Schema::hasTable('post_metas')) {
            return ['count' => 0, 'latest_reply_post_id' => 0];
        }

        $myPosts = $this->findUserPosts($user);
        $myPostIds = $this->extractPostIds($myPosts);

        if ($myPostIds === []) {
            return ['count' => 0, 'latest_reply_post_id' => 0];
        }

        $candidates = $this->findCandidateReplies($myPostIds);
        $replyPostIds = [];
        $latestReplyPostId = 0;

        foreach ($candidates as $replyPost) {
            if ($replyPost->is_deleted) {
                continue;
            }

            $replyPostId = (int) $replyPost->id;
            if ($replyPostId <= $afterReplyPostId) {
                continue;
            }

            if ($this->findTargetHits((string) $replyPost->body, $myPostIds) === []) {
                continue;
            }

            $replyPostIds[$replyPostId] = true;
            $latestReplyPostId = max($latestReplyPostId, $replyPostId);
        }

        return [
            'count' => count($replyPostIds),
            'latest_reply_post_id' => $latestReplyPostId,
        ];
    }

    private function findUserPosts(User $user): Collection
    {
        $abuseId = AbuseId::fromUserId($user->id)->value();

        return Post::query()
            ->select(['posts.id', 'posts.thread_id', 'posts.created_at'])
            ->whereHas('meta', function ($query) use ($abuseId): void {
                $query->where('abuse_id', $abuseId)
                    ->where('epoch', PostingGuard::EPOCH);
            })
            ->with(['thread.board'])
            ->latest('id')
            ->limit(self::USER_POST_SCAN_LIMIT)
            ->get();
    }

    private function extractPostIds(Collection $posts): array
    {
        return $posts->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function findCandidateReplies(array $excludedPostIds): Collection
    {
        return Post::query()
            ->select(['id', 'thread_id', 'body', 'created_at', 'is_deleted'])
            ->where('body', 'like', '%>>%')
            ->whereNotIn('id', $excludedPostIds)
            ->with(['thread.board'])
            ->latest('id')
            ->limit(self::CANDIDATE_SCAN_LIMIT)
            ->get();
    }

    private function buildQuoteLinksForCandidates(Collection $candidates): array
    {
        return $this->quoteLinkResolver->buildQuoteLinks(
            $this->formatter->extractQuoteIds($candidates->pluck('body')->all())
        );
    }

    private function buildReplyRows(
        Collection $candidates,
        array $myPostIds,
        Collection $myPostLookup,
        array $quoteLinks
    ): Collection {
        $rows = collect();

        foreach ($candidates as $replyPost) {
            if ($replyPost->is_deleted) {
                continue;
            }

            $hits = $this->findTargetHits((string) $replyPost->body, $myPostIds);
            if ($hits === []) {
                continue;
            }

            $preview = $this->renderReplyPreview((string) $replyPost->body, $quoteLinks);

            foreach ($hits as $targetPostId) {
                $targetPost = $myPostLookup->get($targetPostId);
                if (! $targetPost instanceof Post) {
                    continue;
                }

                $rows->push($this->buildReplyRow($replyPost, $targetPost, $targetPostId, $preview));
            }
        }

        return $rows;
    }

    private function findTargetHits(string $body, array $myPostIds): array
    {
        $quotedIds = $this->formatter->extractQuoteIds([$body]);

        return array_values(array_intersect($quotedIds, $myPostIds));
    }

    private function renderReplyPreview(string $body, array $quoteLinks): string
    {
        return $this->formatter->format(
            body: Str::limit($body, self::PREVIEW_LENGTH),
            quoteResolver: function (int $postId) use ($quoteLinks): ?array {
                $target = $quoteLinks[$postId] ?? null;
                if (! $target) {
                    return null;
                }

                return [
                    'href' => $target['href'],
                    'new_tab' => false,
                ];
            }
        );
    }

    private function buildReplyRow(Post $replyPost, Post $targetPost, int $targetPostId, string $preview): array
    {
        return [
            'reply_post_id' => (int) $replyPost->id,
            'reply_post_created_at' => $replyPost->created_at,
            'reply_post_preview' => $preview,
            'reply_thread_id' => (int) $replyPost->thread_id,
            'reply_board_slug' => $replyPost->thread?->board?->slug,
            'target_post_id' => $targetPostId,
            'target_thread_id' => (int) $targetPost->thread_id,
            'target_board_slug' => $targetPost->thread?->board?->slug,
        ];
    }
}
