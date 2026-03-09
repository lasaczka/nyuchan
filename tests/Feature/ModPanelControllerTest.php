<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Ban;
use App\Models\User;
use App\Support\PostingGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModPanelControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_open_mod_panel(): void
    {
        $user = User::factory()->create(['role' => Role::User]);

        $this->actingAs($user)
            ->get(route('mod.index'))
            ->assertForbidden();
    }

    public function test_moderator_can_toggle_mod_tools_visibility(): void
    {
        $mod = User::factory()->create(['role' => Role::Mod]);

        $this->actingAs($mod)
            ->post(route('mod.tools.toggle'), ['show_mod_tools' => '0'])
            ->assertRedirect()
            ->assertSessionHas('show_mod_tools', false);
    }

    public function test_admin_can_unban_existing_ban(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $target = User::factory()->create(['role' => Role::User]);

        $ban = Ban::query()->create([
            'abuse_id' => PostingGuard::abuseId($target->id)->value(),
            'epoch' => PostingGuard::EPOCH,
            'reason' => 'temp',
            'expires_at' => now()->addHour(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('mod.bans.unban', ['ban' => $ban->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('bans', ['id' => $ban->id]);
    }

    public function test_admin_self_demotion_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)
            ->post(route('mod.users.role', ['targetUser' => $admin->id]), [
                'role' => Role::User->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(Role::Admin, $admin->refresh()->role);
    }
}

