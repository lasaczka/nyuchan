<?php

namespace App\Http\Controllers;

use App\Enums\SiteLocale;
use App\Enums\SiteTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function set(Request $request): RedirectResponse
    {
        $locale = SiteLocale::fromNullable((string) $request->query('locale', SiteLocale::default()->value))->value;
        $theme = SiteTheme::fromNullable((string) $request->query('theme', SiteTheme::default()->value))->value;

        $request->session()->put('locale', $locale);
        $request->session()->put('theme', $theme);

        return redirect()->back()
            ->withCookie(cookie()->forever('locale', $locale))
            ->withCookie(cookie()->forever('theme', $theme));
    }
}
