<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModPanelEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_announcement_returns_status_when_table_missing(): void
    {
        $mod = User::factory()->create(['role' => Role::Mod]);
        Schema::drop('announcements');

        $this->actingAs($mod)
            ->post(route('mod.announcements.store'), [
                'title' => 'Title',
                'body' => 'Body',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');
    }

    public function test_publish_announcement_keeps_existing_published_timestamp(): void
    {
        $mod = User::factory()->create(['role' => Role::Mod]);
        $announcement = Announcement::query()->create([
            'title' => 'Published',
            'body' => 'Body',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'created_by_user_id' => $mod->id,
        ]);
        $originalPublishedAt = $announcement->published_at;

        $this->actingAs($mod)
            ->post(route('mod.announcements.publish', ['announcement' => $announcement->id]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $announcement->refresh();
        $this->assertTrue((bool) $announcement->is_published);
        $this->assertSame($originalPublishedAt?->getTimestamp(), $announcement->published_at?->getTimestamp());
    }

    public function test_regular_user_cannot_unban_user(): void
    {
        $regularUser = User::factory()->create(['role' => Role::User]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $ban = \App\Models\Ban::query()->create([
            'abuse_id' => \App\Support\PostingGuard::abuseId($admin->id)->value(),
            'epoch' => \App\Support\PostingGuard::EPOCH,
            'reason' => 'test',
            'expires_at' => now()->addHour(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($regularUser)
            ->post(route('mod.bans.unban', ['ban' => $ban->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('bans', ['id' => $ban->id]);
    }
}
