<?php

namespace Tests\Feature;

use App\Enums\SiteLocale;
use App\Enums\SiteTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleThemeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_controller_applies_valid_values(): void
    {
        $this->get(route('locale.set', ['locale' => SiteLocale::Ru->value, 'theme' => SiteTheme::Futaba->value]))
            ->assertRedirect();

        $this->assertSame(SiteLocale::Ru->value, session('locale'));
        $this->assertSame(SiteTheme::Futaba->value, session('theme'));
    }

    public function test_locale_controller_falls_back_to_defaults_for_invalid_values(): void
    {
        $this->get(route('locale.set', ['locale' => 'xx', 'theme' => 'unknown']))
            ->assertRedirect();

        $this->assertSame(SiteLocale::default()->value, session('locale'));
        $this->assertSame(SiteTheme::default()->value, session('theme'));
    }

    public function test_theme_controller_falls_back_to_default_for_invalid_theme(): void
    {
        $this->get(route('theme.set', ['theme' => 'invalid']))
            ->assertRedirect();

        $this->assertSame(SiteTheme::default()->value, session('theme'));
    }
}

