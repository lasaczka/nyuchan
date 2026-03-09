<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Support\PostingGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileRepliesAndFavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_scroll_container_for_favorites_when_more_than_ten(): void
    {
        $user = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
        ]);

        $threads = collect();
        for ($i = 1; $i <= 11; $i++) {
            $threads->push(Thread::query()->create([
                'board_id' => $board->id,
                'title' => 'Thread '.$i,
                'bumped_at' => now(),
                'is_locked' => false,
                'owner_token_hash' => hash('sha256', 'owner-'.$i),
                'owner_token_issued_at' => now(),
            ]));
        }

        $user->favoriteThreads()->attach($threads->pluck('id')->all());

        $this->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'favorites']))
            ->assertOk()
            ->assertSee('favorites-scroll', false);
    }

    public function test_profile_replies_are_paginated_by_ten(): void
    {
        $user = User::factory()->create(['username' => 'kitsune']);
        $replyAuthor = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Reply test',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-reply'),
            'owner_token_issued_at' => now(),
        ]);

        $targetPost = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => $user->username,
            'display_color' => $user->profile_color,
            'body' => 'target post',
            'is_sage' => false,
            'is_deleted' => false,
        ]);
        PostingGuard::stampPost($targetPost, PostingGuard::abuseId($user->id));

        for ($i = 1; $i <= 12; $i++) {
            Post::query()->create([
                'thread_id' => $thread->id,
                'display_name' => $replyAuthor->username,
                'display_color' => null,
                'body' => '>>'.$targetPost->id.' reply-token-'.$i,
                'is_sage' => false,
                'is_deleted' => false,
            ]);
        }

        $page1 = $this->actingAs($user)->get(route('profile.edit', ['tab' => 'favorites']));
        $page1->assertOk()
            ->assertSee('reply-token-12')
            ->assertSee('reply-token-3')
            ->assertDontSee('reply-token-2')
            ->assertSee('replies_page=2', false);

        $page2 = $this->actingAs($user)->get(route('profile.edit', ['tab' => 'favorites', 'replies_page' => 2]));
        $page2->assertOk()
            ->assertSee('reply-token-2')
            ->assertSee('reply-token-1')
            ->assertDontSee('reply-token-12');
    }
}

