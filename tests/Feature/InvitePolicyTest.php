<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvitePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_can_generate_only_one_invite_per_hour(): void
    {
        Carbon::setTestNow('2026-03-06 10:00:00');
        $user = User::factory()->create(['role' => Role::User]);

        $this->actingAs($user)->post(route('invites.create'))
            ->assertSessionHas('invite');

        $this->actingAs($user)->post(route('invites.create'))
            ->assertSessionHasErrors('invite');

        $this->assertCount(1, Invite::query()->where('created_by_user_id', $user->id)->get());
        Carbon::setTestNow();
    }

    public function test_moderator_can_generate_one_invite_per_ten_minutes(): void
    {
        Carbon::setTestNow('2026-03-06 10:00:00');
        $mod = User::factory()->create(['role' => Role::Mod]);

        $this->actingAs($mod)->post(route('invites.create'))
            ->assertSessionHas('invite');
        $this->actingAs($mod)->post(route('invites.create'))
            ->assertSessionHasErrors('invite');

        Carbon::setTestNow('2026-03-06 10:11:00');
        $this->actingAs($mod)->post(route('invites.create'))
            ->assertSessionHas('invite');

        $this->assertCount(2, Invite::query()->where('created_by_user_id', $mod->id)->get());
        Carbon::setTestNow();
    }

    public function test_admin_has_no_invite_rate_limit(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->post(route('invites.create'))
            ->assertSessionHas('invite');
        $this->actingAs($admin)->post(route('invites.create'))
            ->assertSessionHas('invite');
        $this->actingAs($admin)->post(route('invites.create'))
            ->assertSessionHas('invite');

        $this->assertCount(3, Invite::query()->where('created_by_user_id', $admin->id)->get());
    }
}

