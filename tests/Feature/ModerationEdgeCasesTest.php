<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Board;
use App\Models\Post;
use App\Models\PostMeta;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_post_uses_default_reason_when_empty(): void
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
            'title' => 'T',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'thread-delete-post-default-reason'),
            'owner_token_issued_at' => now(),
        ]);
        $post = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => 'anon',
            'display_color' => null,
            'body' => 'hello',
            'is_deleted' => false,
            'is_sage' => false,
        ]);

        $this->actingAs($mod)
            ->post(route('mod.post.delete', ['board' => $board->slug, 'thread' => $thread->id, 'post' => $post->id]), [
                'reason' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mod_actions', [
            'action' => 'delete_post',
            'target_type' => Post::class,
            'target_id' => $post->id,
            'reason' => 'no reason',
        ]);
    }

    public function test_ban_post_author_returns_422_when_author_is_unknown(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'T',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'thread-ban-unknown'),
            'owner_token_issued_at' => now(),
        ]);
        $post = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => 'anon',
            'display_color' => null,
            'body' => 'hello',
            'is_deleted' => false,
            'is_sage' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]))
            ->post(route('mod.post.ban', ['board' => $board->slug, 'thread' => $thread->id, 'post' => $post->id]), [
                'minutes' => 60,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('ban');
    }

    public function test_ban_post_author_does_not_overwrite_already_deleted_post(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $author = User::factory()->create();

        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 250,
            'is_hidden' => false,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'T',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'thread-ban-deleted'),
            'owner_token_issued_at' => now(),
        ]);
        $post = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => '',
            'is_deleted' => true,
            'is_sage' => false,
        ]);
        PostMeta::query()->create([
            'post_id' => $post->id,
            'abuse_id' => \App\Support\PostingGuard::abuseId($author->id)->value(),
            'epoch' => \App\Support\PostingGuard::EPOCH,
        ]);

        $this->actingAs($admin)
            ->post(route('mod.post.ban', ['board' => $board->slug, 'thread' => $thread->id, 'post' => $post->id]), [
                'minutes' => 60,
                'reason' => 'rule break',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('bans', 1);
        $this->assertSame('', $post->refresh()->body);
        $this->assertTrue((bool) $post->is_deleted);
    }
}
