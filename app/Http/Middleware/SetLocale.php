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
        if (! is_array($available) || $available === []) {
            $available = ['bn', 'en'];
        }

        $default = config('app.locale', 'bn') ?: 'bn';
        $locale = $default;

        try {
            if ($request->hasSession()) {
                $locale = $request->session()->get('locale', $default);
            }
        } catch (\Throwable) {
            $locale = $default;
        }

        if (! in_array($locale, $available, true)) {
            $locale = in_array($default, $available, true) ? $default : 'bn';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
