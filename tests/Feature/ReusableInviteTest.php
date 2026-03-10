<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReusableInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_revoke_reusable_invite(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $response = $this->actingAs($admin)->post(route('mod.invites.reusable.store'), [
            'max_uses' => 3,
            'expires_in_minutes' => 60,
        ]);

        $response->assertSessionHas('invite');

        $invite = Invite::query()->latest('id')->first();
        $this->assertNotNull($invite);
        $this->assertSame(3, (int) $invite->max_uses);
        $this->assertSame(0, (int) $invite->uses_count);
        $this->assertTrue((bool) $invite->is_active);

        $this->actingAs($admin)
            ->post(route('mod.invites.reusable.revoke', ['invite' => $invite->id]))
            ->assertSessionHas('status');

        $this->assertFalse((bool) $invite->fresh()->is_active);
    }

    public function test_reusable_invite_is_consumed_until_limit_then_rejected(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $invite = Invite::query()->create([
            'token' => 'REUSE-ME-INVITE-KEY-0001',
            'max_uses' => 2,
            'uses_count' => 0,
            'created_by_user_id' => $admin->id,
            'is_active' => true,
        ]);

        $this->post('/register', [
            'username' => 'user_one',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite' => $invite->token,
        ])->assertRedirect(route('recovery.key.show', absolute: false));

        $this->post('/logout');

        $this->post('/register', [
            'username' => 'user_two',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite' => $invite->token,
        ])->assertRedirect(route('recovery.key.show', absolute: false));

        $this->post('/logout');

        $third = $this->from('/register')->post('/register', [
            'username' => 'user_three',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite' => $invite->token,
        ]);

        $third->assertRedirect('/register');
        $third->assertSessionHasErrors('invite');

        $invite->refresh();
        $this->assertSame(2, (int) $invite->uses_count);
        $this->assertFalse((bool) $invite->is_active);
    }

    public function test_expired_invite_is_rejected(): void
    {
        Carbon::setTestNow('2026-03-10 12:00:00');

        $admin = User::factory()->create(['role' => Role::Admin]);
        $invite = Invite::query()->create([
            'token' => 'EXPIRED-INVITE-KEY-0002',
            'max_uses' => null,
            'uses_count' => 0,
            'created_by_user_id' => $admin->id,
            'expires_at' => now()->subMinute(),
            'is_active' => true,
        ]);

        $response = $this->from('/register')->post('/register', [
            'username' => 'late_user',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite' => $invite->token,
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('invite');

        Carbon::setTestNow();
    }
}

