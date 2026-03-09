<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HomeFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_handles_missing_announcements_table(): void
    {
        Schema::drop('announcements');

        $this->get('/')
            ->assertOk();
    }

    public function test_homepage_handles_missing_boards_table(): void
    {
        Schema::drop('boards');

        $this->get('/')
            ->assertOk();
    }
}
