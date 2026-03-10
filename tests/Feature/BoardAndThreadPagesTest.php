<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardAndThreadPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_page_renders_thread_previews(): void
    {
        $user = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 3,
            'post_rate_limit_window_seconds' => 60,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Preview thread',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-preview'),
            'owner_token_issued_at' => now(),
        ]);
        Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'op body',
            'is_deleted' => false,
            'is_sage' => false,
        ]);
        Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => '>>1 reply body',
            'is_deleted' => false,
            'is_sage' => false,
        ]);

        $this->actingAs($user)
            ->get(route('boards.show', ['board' => $board->slug]))
            ->assertOk()
            ->assertSee('Preview thread');
    }

    public function test_thread_page_renders_posts(): void
    {
        $user = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 3,
            'post_rate_limit_window_seconds' => 60,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Thread show',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-show'),
            'owner_token_issued_at' => now(),
        ]);
        Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'first post body',
            'is_deleted' => false,
            'is_sage' => false,
        ]);

        $this->actingAs($user)
            ->get(route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]))
            ->assertOk()
            ->assertSee('first post body');
    }

    public function test_user_can_create_thread(): void
    {
        $user = User::factory()->create(['username' => 'kitsune']);
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 10,
            'post_rate_limit_window_seconds' => 60,
            'thread_limit' => 100,
        ]);

        $this->actingAs($user)
            ->post(route('threads.store', ['board' => $board->slug]), [
                'title' => 'New thread title',
                'body' => 'op content',
            ])
            ->assertRedirect();

        $thread = Thread::query()->where('board_id', $board->id)->where('title', 'New thread title')->first();
        $this->assertNotNull($thread);
        $this->assertDatabaseHas('posts', [
            'thread_id' => $thread->id,
            'body' => 'op content',
        ]);
    }

    public function test_thread_reply_form_is_opened_when_quote_parameter_is_present(): void
    {
        $user = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 3,
            'post_rate_limit_window_seconds' => 60,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Thread quote open',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-quote'),
            'owner_token_issued_at' => now(),
        ]);
        $post = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'post body',
            'is_deleted' => false,
            'is_sage' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('threads.show', ['board' => $board->slug, 'thread' => $thread->id, 'quote' => $post->id]));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/<details class="[^"]*\breply-details\b[^"]*"[^>]*\bopen\b[^>]*>/',
            (string) $response->getContent()
        );
    }

    public function test_board_search_filters_threads_by_title_and_post_body(): void
    {
        $user = User::factory()->create();
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
            'post_rate_limit_count' => 3,
            'post_rate_limit_window_seconds' => 60,
        ]);

        $threadByTitle = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Linux tips',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-search-title'),
            'owner_token_issued_at' => now(),
        ]);
        Post::query()->create([
            'thread_id' => $threadByTitle->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'op',
            'is_deleted' => false,
            'is_sage' => false,
        ]);

        $threadByBody = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Random chat',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-search-body'),
            'owner_token_issued_at' => now(),
        ]);
        Post::query()->create([
            'thread_id' => $threadByBody->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'kernel panic',
            'is_deleted' => false,
            'is_sage' => false,
        ]);

        $threadNoMatch = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Cooking',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-search-none'),
            'owner_token_issued_at' => now(),
        ]);
        Post::query()->create([
            'thread_id' => $threadNoMatch->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'recipe',
            'is_deleted' => false,
            'is_sage' => false,
        ]);

        $this->actingAs($user)
            ->get(route('boards.show', ['board' => $board->slug, 'q' => 'kernel']))
            ->assertOk()
            ->assertSee('Random chat')
            ->assertDontSee('Linux tips')
            ->assertDontSee('Cooking');

        $this->actingAs($user)
            ->get(route('boards.show', ['board' => $board->slug, 'q' => 'linux']))
            ->assertOk()
            ->assertSee('Linux tips')
            ->assertDontSee('Random chat')
            ->assertDontSee('Cooking');
    }
}
