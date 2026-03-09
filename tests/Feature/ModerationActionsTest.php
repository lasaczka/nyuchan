<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Board;
use App\Models\Post;
use App\Models\PostMeta;
use App\Models\Thread;
use App\Models\User;
use App\Support\PostingGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_board_settings(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'thread_limit' => 100,
            'bump_limit' => 250,
            'post_rate_limit_count' => 3,
            'post_rate_limit_window_seconds' => 60,
            'is_hidden' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('mod.board.settings', ['board' => $board->slug]), [
                'thread_limit' => 120,
                'bump_limit' => 300,
                'post_rate_limit_count' => 4,
                'post_rate_limit_window_seconds' => 90,
            ])
            ->assertRedirect();

        $board->refresh();
        $this->assertSame(120, (int) $board->thread_limit);
        $this->assertSame(300, (int) $board->bump_limit);
        $this->assertSame(4, (int) $board->post_rate_limit_count);
        $this->assertSame(90, (int) $board->post_rate_limit_window_seconds);
    }

    public function test_moderator_can_delete_thread(): void
    {
        $mod = User::factory()->create(['role' => Role::Mod]);
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Delete me',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-delete-thread'),
            'owner_token_issued_at' => now(),
        ]);

        $this->actingAs($mod)
            ->post(route('mod.thread.delete', ['board' => $board->slug, 'thread' => $thread->id]), [
                'reason' => 'cleanup',
            ])
            ->assertRedirect(route('boards.show', ['board' => $board->slug]));

        $this->assertDatabaseMissing('threads', ['id' => $thread->id]);
        $this->assertDatabaseHas('mod_actions', [
            'action' => 'delete_thread',
            'target_type' => Thread::class,
            'target_id' => $thread->id,
        ]);
    }

    public function test_admin_can_ban_thread_author_and_delete_thread(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $author = User::factory()->create(['role' => Role::User]);
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'Ban thread author',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'owner-ban-thread'),
            'owner_token_issued_at' => now(),
        ]);
        $op = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'op',
            'is_deleted' => false,
            'is_sage' => false,
        ]);
        PostMeta::query()->create([
            'post_id' => $op->id,
            'abuse_id' => PostingGuard::abuseId($author->id)->value(),
            'epoch' => PostingGuard::EPOCH,
        ]);

        $this->actingAs($admin)
            ->post(route('mod.thread.ban_author', ['board' => $board->slug, 'thread' => $thread->id]), [
                'minutes' => 60,
                'reason' => 'spam',
            ])
            ->assertRedirect(route('boards.show', ['board' => $board->slug]));

        $this->assertDatabaseMissing('threads', ['id' => $thread->id]);
        $this->assertDatabaseHas('bans', [
            'abuse_id' => PostingGuard::abuseId($author->id)->value(),
            'epoch' => PostingGuard::EPOCH,
        ]);
    }
}

