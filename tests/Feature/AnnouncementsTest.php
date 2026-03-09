<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_create_draft_announcement_and_publish_it(): void
    {
        $mod = User::factory()->create(['role' => Role::Mod]);

        $this->actingAs($mod)
            ->post(route('mod.announcements.store'), [
                'title' => 'Draft title',
                'body' => 'Draft body',
            ])
            ->assertRedirect();

        $announcement = Announcement::query()->latest('id')->first();
        $this->assertNotNull($announcement);
        $this->assertSame('Draft title', $announcement->title);
        $this->assertFalse((bool) $announcement->is_published);
        $this->assertNull($announcement->published_at);

        $this->actingAs($mod)
            ->post(route('mod.announcements.publish', ['announcement' => $announcement->id]))
            ->assertRedirect();

        $announcement->refresh();
        $this->assertTrue((bool) $announcement->is_published);
        $this->assertNotNull($announcement->published_at);
    }
}

