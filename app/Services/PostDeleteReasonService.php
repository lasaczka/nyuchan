<?php

namespace App\Services;

use App\Models\ModAction;
use App\Models\Post;

class PostDeleteReasonService
{
    public function loadForPosts(array $postIds): array
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

