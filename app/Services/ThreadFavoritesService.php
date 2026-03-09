<?php

namespace App\Services;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ThreadFavoritesService
{
    public function isAvailable(): bool
    {
        return Schema::hasTable('user_favorite_threads');
    }

    public function listFavoriteThreadIds(User $user): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        return $user->favoriteThreads()
            ->pluck('threads.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function isFavorite(User $user, Thread $thread): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return $user->favoriteThreads()->where('threads.id', $thread->id)->exists();
    }

    public function toggle(User $user, Thread $thread): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $exists = $this->isFavorite($user, $thread);

        if ($exists) {
            $user->favoriteThreads()->detach($thread->id);

            return false;
        }

        $user->favoriteThreads()->attach($thread->id);

        return true;
    }

    public function listFavoriteThreads(User $user, ?int $limit = 200): Collection
    {
        if (! $this->isAvailable()) {
            return collect();
        }

        $query = $user->favoriteThreads()
            ->with(['board:id,slug,title,is_hidden'])
            ->orderByPivot('created_at', 'desc');

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
