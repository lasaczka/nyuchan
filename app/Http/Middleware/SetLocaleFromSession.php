<?php

namespace App\Http\Middleware;

use App\Enums\SiteLocale;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionLocale = (string) $request->session()->get('locale', config('app.locale', SiteLocale::default()->value));
        $locale = SiteLocale::fromNullable($sessionLocale)->value;

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
