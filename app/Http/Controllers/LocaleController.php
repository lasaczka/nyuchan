<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const LOCALES = ['be', 'ru', 'en'];
    private const THEMES = ['sugar', 'makaba', 're-l', 'nyu', 'futaba', 'yotsuba', 'lelouch'];

    public function set(Request $request): RedirectResponse
    {
        $locale = (string) $request->query('locale', 'be');
        $theme = (string) $request->query('theme', 'sugar');

        if (! in_array($locale, self::LOCALES, true)) {
            $locale = 'be';
        }

        if (! in_array($theme, self::THEMES, true)) {
            $theme = 'sugar';
        }

        $request->session()->put('locale', $locale);
        $request->session()->put('theme', $theme);

        return redirect()->back()
            ->withCookie(cookie()->forever('locale', $locale))
            ->withCookie(cookie()->forever('theme', $theme));
    }
}
