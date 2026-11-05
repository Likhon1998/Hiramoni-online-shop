<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = config('app.available_locales', ['bn', 'en']);
        $default = config('app.locale', 'bn');

        $locale = $request->session()->get('locale', $default);
        if (! in_array($locale, $available, true)) {
            $locale = $default;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
