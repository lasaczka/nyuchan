<?php

namespace Tests\Feature\Auth;

use App\Models\Invite;
use App\Models\User;
use App\Models\UserRecoveryKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_with_invite(): void
    {
        $inviter = User::factory()->create();

        $invite = Invite::create([
            'token' => Str::random(32),
            'created_by_user_id' => $inviter->id,
        ]);

        $response = $this->post('/register', [
            'username' => 'newuser',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite' => $invite->token,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('recovery.key.show', absolute: false));
        $response->assertSessionHas('recovery_key');

        $invite->refresh();
        $this->assertNotNull($invite->used_at);
        $this->assertNotNull($invite->used_by_user_id);

        $user = User::query()->where('username', 'newuser')->first();
        $this->assertNotNull($user);
        $this->assertNotNull(UserRecoveryKey::query()->where('user_id', $user->id)->first());
    }

    public function test_registration_fails_with_invalid_invite(): void
    {
        $response = $this->post('/register', [
            'username' => 'newuser',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite' => 'invalid-token',
        ]);

        $response->assertSessionHasErrors('invite');
        $this->assertGuest();
    }
}
