<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\Role;
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

    public function test_403_page_uses_localized_random_content(): void
    {
        config()->set('app.debug', false);

        $user = User::factory()->create(['role' => Role::User]);
        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('mod.index'));

        $response->assertStatus(403);
        $response->assertSee('403');
        $response->assertSee(__('ui.error_403_title'));

        $pool = trans('ui.error_403_pool');
        $pool = is_array($pool) ? $pool : [];
        $content = $response->getContent();
        $found = false;
        foreach ($pool as $line) {
            if (str_contains($content, $line)) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Expected one random 403 phrase to be rendered.');
    }
}
