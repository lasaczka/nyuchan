<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\TripcodeService;
use Tests\TestCase;

class TripcodeServiceTest extends TestCase
{
    public function test_modern_tripcode_is_generated_with_app_key(): void
    {
        config()->set('nyuchan.tripcodes.algorithm', 'modern');
        config()->set('app.key', 'base64:2LwODyw8E8+X8m5g7gW6fHi0A4fRWCF7S8XTRrEJfIU=');

        $user = new User([
            'username' => 'kitsune',
            'use_tripcode' => true,
            'tripcode_secret' => 'secret-123',
        ]);

        $trip = app(TripcodeService::class)->generateForUser($user);

        $this->assertNotNull($trip);
        $this->assertSame(11, strlen((string) $trip));
        $this->assertStringStartsWith('!', (string) $trip);
    }

    public function test_traditional_tripcode_matches_expected_output(): void
    {
        config()->set('nyuchan.tripcodes.algorithm', 'traditional');

        $user = new User([
            'username' => 'kitsune',
            'use_tripcode' => true,
            'tripcode_secret' => 'password',
        ]);

        $trip = app(TripcodeService::class)->generateForUser($user);

        $this->assertSame('!ozOtJW9BFA', $trip);
    }

    public function test_unknown_algorithm_falls_back_to_modern(): void
    {
        config()->set('nyuchan.tripcodes.algorithm', 'unknown');
        config()->set('app.key', 'base64:2LwODyw8E8+X8m5g7gW6fHi0A4fRWCF7S8XTRrEJfIU=');

        $user = new User([
            'username' => 'kitsune',
            'use_tripcode' => true,
            'tripcode_secret' => 'secret-123',
        ]);

        $trip = app(TripcodeService::class)->generateForUser($user);

        $this->assertNotNull($trip);
        $this->assertStringStartsWith('!', (string) $trip);
        $this->assertSame(11, strlen((string) $trip));
    }
}

