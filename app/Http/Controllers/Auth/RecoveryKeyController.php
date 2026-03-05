<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecoveryKeyController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $key = $request->session()->get('recovery_key');

        if (! $key) {
            return $request->user()
                ? redirect()->route('dashboard')
                : redirect()->route('login');
        }

        return view('auth.recovery-key', [
            'recoveryKey' => $key,
        ]);
    }

    public function acknowledge(Request $request): RedirectResponse
    {
        $request->validate([
            'saved' => ['accepted'],
        ]);

        $request->session()->forget('recovery_key');

        return $request->user()
            ? redirect()->route('dashboard')->with('status', 'Recovery key acknowledged.')
            : redirect()->route('login')->with('status', 'Recovery key acknowledged.');
    }
}
