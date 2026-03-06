<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    private const THEMES = ['sugar', 'makaba', 're-l', 'nyu', 'futaba', 'yotsuba', 'lelouch'];

    public function set(Request $request, string $theme): RedirectResponse
    {
        if (! in_array($theme, self::THEMES, true)) {
            $theme = 'sugar';
        }

        $request->session()->put('theme', $theme);

        return redirect()->back();
    }
}
