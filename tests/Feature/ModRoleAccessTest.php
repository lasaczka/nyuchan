<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_cannot_change_user_roles(): void
    {
        $mod = User::factory()->create(['role' => Role::Mod]);
        $target = User::factory()->create(['role' => Role::User]);

        $this->actingAs($mod)
            ->post(route('mod.users.role', ['targetUser' => $target->id]), [
                'role' => Role::Admin->value,
            ])
            ->assertForbidden();

        $this->assertSame(Role::User, $target->refresh()->role);
    }

    public function test_admin_can_change_user_roles(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $target = User::factory()->create(['role' => Role::User]);

        $this->actingAs($admin)
            ->post(route('mod.users.role', ['targetUser' => $target->id]), [
                'role' => Role::Mod->value,
            ])
            ->assertRedirect();

        $this->assertSame(Role::Mod, $target->refresh()->role);
    }
}

