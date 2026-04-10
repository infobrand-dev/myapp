<?php

namespace App\Support;

use Illuminate\Http\Request;

class AccountingUiMode
{
    public const STANDARD = 'standard';
    public const ADVANCED = 'advanced';
    public const SESSION_KEY = 'accounting_ui_mode';

    public function current(?Request $request = null): string
    {
        $request ??= request();

        $mode = (string) ($request->session()->get(self::SESSION_KEY, self::STANDARD));

        return in_array($mode, [self::STANDARD, self::ADVANCED], true)
            ? $mode
            : self::STANDARD;
    }

    public function isAdvanced(?Request $request = null): bool
    {
        return $this->current($request) === self::ADVANCED;
    }

    public function set(Request $request, string $mode): string
    {
        $resolved = in_array($mode, [self::STANDARD, self::ADVANCED], true)
            ? $mode
            : self::STANDARD;

        $request->session()->put(self::SESSION_KEY, $resolved);

        return $resolved;
    }
}
