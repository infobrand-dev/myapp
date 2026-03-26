<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const SUPPORTED = ['en', 'id'];

    public function switch(Request $request): RedirectResponse
    {
        $locale = $request->input('locale');

        if (!in_array($locale, self::SUPPORTED)) {
            return back();
        }

        session(['locale' => $locale]);

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        return back();
    }
}
