<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Support\PostingGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRepliesNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_new_replies_notice_and_can_mark_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

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
            'owner_token_hash' => hash('sha256', 'owner-home-replies-notice'),
            'owner_token_issued_at' => now(),
        ]);

        $myPost = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'my post',
            'is_deleted' => false,
            'is_sage' => false,
        ]);
        PostingGuard::stampPost($myPost, PostingGuard::abuseId($user->id));

        $reply = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => '>>'.$myPost->id.' reply',
            'is_deleted' => false,
            'is_sage' => false,
        ]);
        PostingGuard::stampPost($reply, PostingGuard::abuseId($other->id));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('ui.new_replies_title').' (1)');

        $this->actingAs($user)
            ->post(route('replies.mark_read'), ['max_reply_post_id' => $reply->id])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($reply->id, (int) $user->refresh()->last_seen_reply_post_id);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('ui.new_replies_title'));
    }

    public function test_opening_profile_favorites_marks_replies_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

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
            'owner_token_hash' => hash('sha256', 'owner-home-replies-profile-read'),
            'owner_token_issued_at' => now(),
        ]);

        $myPost = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => 'my post',
            'is_deleted' => false,
            'is_sage' => false,
        ]);
        PostingGuard::stampPost($myPost, PostingGuard::abuseId($user->id));

        $reply = Post::query()->create([
            'thread_id' => $thread->id,
            'display_name' => null,
            'display_color' => null,
            'body' => '>>'.$myPost->id.' reply',
            'is_deleted' => false,
            'is_sage' => false,
        ]);
        PostingGuard::stampPost($reply, PostingGuard::abuseId($other->id));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('ui.new_replies_title').' (1)');

        $this->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'favorites']))
            ->assertOk();

        $this->assertSame($reply->id, (int) $user->fresh()->last_seen_reply_post_id);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('ui.new_replies_title'));
    }
}
