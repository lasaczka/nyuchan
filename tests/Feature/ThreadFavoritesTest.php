<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadFavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_thread_favorite(): void
    {
        $user = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Favorite me',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-x'),
            'owner_token_issued_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]))
            ->post(route('threads.favorite.toggle', ['board' => $board->slug, 'thread' => $thread->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('user_favorite_threads', [
            'user_id' => $user->id,
            'thread_id' => $thread->id,
        ]);

        $this->actingAs($user)
            ->from(route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]))
            ->post(route('threads.favorite.toggle', ['board' => $board->slug, 'thread' => $thread->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('user_favorite_threads', [
            'user_id' => $user->id,
            'thread_id' => $thread->id,
        ]);
    }
}

