<?php

namespace App\Http\Controllers;

use App\Enums\SiteTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function set(Request $request, string $theme): RedirectResponse
    {
        $request->session()->put('theme', SiteTheme::fromNullable($theme)->value);

        return redirect()->back();
    }
}
