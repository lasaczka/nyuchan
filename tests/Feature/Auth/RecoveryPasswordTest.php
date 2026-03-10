<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserRecoveryKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RecoveryPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/recover-password');

        $response->assertOk();
    }

    public function test_password_can_be_reset_with_valid_recovery_key(): void
    {
        $user = User::factory()->create([
            'username' => 'recoverme',
            'password' => 'password',
        ]);

        $recoveryKey = UserRecoveryKey::issueFor($user);

        $response = $this->post('/recover-password', [
            'username' => 'recoverme',
            'recovery_key' => $recoveryKey->value(),
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('recovery.key.show', absolute: false));
        $response->assertSessionHas('recovery_key');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

        $stored = UserRecoveryKey::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($stored);
        $this->assertNull($stored->used_at);
    }

    public function test_password_can_not_be_reset_with_invalid_recovery_key(): void
    {
        $user = User::factory()->create([
            'username' => 'recoverme',
        ]);

        UserRecoveryKey::issueFor($user);

        $response = $this->from('/recover-password')->post('/recover-password', [
            'username' => 'recoverme',
            'recovery_key' => 'WRONGKEY',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/recover-password');
        $response->assertSessionHasErrors('recovery_key');
    }

    public function test_recovery_key_acknowledgement_clears_key_from_session(): void
    {
        $response = $this->withSession(['recovery_key' => 'TEST-KEY'])->post('/recovery-key/ack', [
            'saved' => '1',
        ]);

        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionMissing('recovery_key');
    }
}
