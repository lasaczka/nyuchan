<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Support\PostingGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PostingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_reply_bumps_thread_when_not_sage(): void
    {
        Carbon::setTestNow('2026-03-09 12:00:00');

        $user = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 10,
            'post_rate_limit_window_seconds' => 60,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'T',
            'bumped_at' => Carbon::parse('2026-03-09 10:00:00'),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner'),
            'owner_token_issued_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('posts.store', ['board' => $board->slug, 'thread' => $thread->id]), [
                'body' => 'regular reply',
            ])
            ->assertRedirect();

        $thread->refresh();
        $this->assertSame('2026-03-09 12:00:00', (string) $thread->bumped_at);

        Carbon::setTestNow();
    }

    public function test_post_reply_with_sage_does_not_bump_thread(): void
    {
        Carbon::setTestNow('2026-03-09 12:10:00');

        $user = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 10,
            'post_rate_limit_window_seconds' => 60,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'T',
            'bumped_at' => Carbon::parse('2026-03-09 10:00:00'),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner'),
            'owner_token_issued_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('posts.store', ['board' => $board->slug, 'thread' => $thread->id]), [
                'body' => 'sage reply',
                'sage' => '1',
            ])
            ->assertRedirect();

        $thread->refresh();
        $this->assertSame('2026-03-09 10:00:00', (string) $thread->bumped_at);

        Carbon::setTestNow();
    }

    public function test_post_rate_limit_blocks_fast_replying(): void
    {
        Carbon::setTestNow('2026-03-09 13:00:00');

        $user = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 1,
            'post_rate_limit_window_seconds' => 3600,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'T',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner'),
            'owner_token_issued_at' => now(),
        ]);

        $first = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'first',
            'is_deleted' => false,
            'is_sage' => false,
        ]);
        PostingGuard::stampPost($first, PostingGuard::abuseId($user->id));

        $this->actingAs($user)
            ->from(route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]))
            ->post(route('posts.store', ['board' => $board->slug, 'thread' => $thread->id]), [
                'body' => 'second too soon',
            ])
            ->assertSessionHasErrors('body');

        Carbon::setTestNow();
    }

    public function test_name_posting_uses_tripcode_instead_of_name_when_enabled_in_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'kitsune',
            'use_tripcode' => true,
            'tripcode_secret' => 'secret-123',
        ]);

        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 10,
            'post_rate_limit_window_seconds' => 60,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'T',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner'),
            'owner_token_issued_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('posts.store', ['board' => $board->slug, 'thread' => $thread->id]), [
                'body' => 'trip reply',
                'use_display_name' => '1',
            ])
            ->assertRedirect();

        $post = Post::query()->latest('id')->firstOrFail();

        $this->assertNull($post->display_name);
        $this->assertNotNull($post->tripcode);
        $this->assertStringStartsWith('!', (string) $post->tripcode);
    }

    public function test_name_posting_can_show_name_with_tripcode_when_enabled_in_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'kitsune',
            'use_tripcode' => true,
            'show_name_with_tripcode' => true,
            'tripcode_secret' => 'secret-123',
        ]);

        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 10,
            'post_rate_limit_window_seconds' => 60,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'T',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner'),
            'owner_token_issued_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('posts.store', ['board' => $board->slug, 'thread' => $thread->id]), [
                'body' => 'trip reply',
                'use_display_name' => '1',
            ])
            ->assertRedirect();

        $post = Post::query()->latest('id')->firstOrFail();

        $this->assertSame('kitsune', $post->display_name);
        $this->assertNotNull($post->tripcode);
        $this->assertStringStartsWith('!', (string) $post->tripcode);
    }
}
