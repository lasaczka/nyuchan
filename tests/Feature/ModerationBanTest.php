<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Ban;
use App\Models\Board;
use App\Models\Post;
use App\Models\PostMeta;
use App\Models\Thread;
use App\Models\User;
use App\Support\PostingGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationBanTest extends TestCase
{
    use RefreshDatabase;

    public function test_ban_post_author_creates_ban_and_deletes_post(): void
    {
        $mod = User::factory()->create(['role' => Role::Mod]);
        $author = User::factory()->create(['role' => Role::User]);
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 300,
            'is_hidden' => false,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'T',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'x'),
            'owner_token_issued_at' => now(),
        ]);
        $post = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => 'user',
            'body' => 'hello',
            'is_deleted' => false,
        ]);
        PostMeta::query()->create([
            'post_id' => $post->id,
            'abuse_id' => PostingGuard::abuseId($author->id)->value(),
            'epoch' => 'auth-v1',
        ]);

        $this->actingAs($mod)->post(route('mod.post.ban', [
            'board' => $board->slug,
            'thread' => $thread->id,
            'post' => $post->id,
        ]), [
            'minutes' => 60,
            'reason' => 'wipe',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bans', ['abuse_id' => PostingGuard::abuseId($author->id)->value(), 'epoch' => 'auth-v1']);
        $this->assertSame(1, (int) $post->refresh()->is_deleted);
        $this->assertSame('', $post->body);
    }

    public function test_cannot_ban_self(): void
    {
        $mod = User::factory()->create(['role' => Role::Mod]);
        $board = Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 300,
            'is_hidden' => false,
        ]);
        $thread = Thread::query()->create([
            'board_id' => $board->id,
            'title' => 'T',
            'bumped_at' => now(),
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'x'),
            'owner_token_issued_at' => now(),
        ]);
        $post = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => 'mod',
            'body' => 'my own post',
            'is_deleted' => false,
        ]);
        PostMeta::query()->create([
            'post_id' => $post->id,
            'abuse_id' => PostingGuard::abuseId($mod->id)->value(),
            'epoch' => 'auth-v1',
        ]);

        $this->actingAs($mod)
            ->from(route('threads.show', ['board' => $board->slug, 'thread' => $thread->id]))
            ->post(route('mod.post.ban', [
                'board' => $board->slug,
                'thread' => $thread->id,
                'post' => $post->id,
            ]), [
                'minutes' => 60,
            ])->assertRedirect()
            ->assertSessionHasErrors('ban');

        $this->assertCount(0, Ban::query()->get());
    }
}
