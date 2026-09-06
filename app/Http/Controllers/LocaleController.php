<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request, string $locale)
    {
        $supported = ['en', 'tr', 'es', 'de', 'fr', 'it', 'pt', 'ru', 'zh', 'ar'];
        if (in_array($locale, $supported)) {
            session(['locale' => $locale]);
            cookie()->queue('seovy_locale', $locale, 60 * 24 * 365);
        }

        return back();
    }
}
