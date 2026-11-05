<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $available = config('app.available_locales', ['bn', 'en']);
        if (! in_array($locale, $available, true)) {
            $locale = config('app.locale', 'bn');
        }

        $request->session()->put('locale', $locale);

        $previous = url()->previous();
        if (! $previous || $previous === $request->fullUrl()) {
            return redirect()->route('home');
        }

        return redirect()->to($previous);
    }
}
