<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_page_uses_localized_random_content_and_recovery_link(): void
    {
        Board::query()->create([
            'slug' => 'a',
            'title' => 'Anime',
            'bump_limit' => 250,
            'is_hidden' => false,
        ]);
        Board::query()->create([
            'slug' => 'b',
            'title' => 'Random',
            'bump_limit' => 300,
            'is_hidden' => false,
        ]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->withSession(['locale' => 'be'])
            ->get('/missing-page-for-404');

        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee(__('ui.error_404_try'));
        $this->assertMatchesRegularExpression('#/(a|b|bb|rf|nsfw)/#', $response->getContent());
    }
}
