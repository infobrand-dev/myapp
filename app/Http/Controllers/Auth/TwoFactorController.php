<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // -----------------------------------------------------------------------
    // Challenge — shown immediately after login when user has 2FA enabled
    // -----------------------------------------------------------------------

    public function showChallenge(Request $request)
    {
        if (! $request->session()->has('two_factor_pending_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function challenge(Request $request)
    {
        $userId = $request->session()->get('two_factor_pending_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = User::findOrFail($userId);

        // Try TOTP code first
        if ($request->filled('code')) {
            $valid = $this->google2fa->verifyKey(
                $user->two_factor_secret,
                (string) $request->code
            );

            if (! $valid) {
                return back()->withErrors(['code' => 'Kode verifikasi tidak valid atau sudah kadaluarsa.']);
            }
        }
        // Then try recovery code
        elseif ($request->filled('recovery_code')) {
            $codes = collect($user->two_factor_recovery_codes ?? []);
            $used  = $codes->first(fn ($c) => hash_equals(trim($c), trim($request->recovery_code)));

            if (! $used) {
                return back()->withErrors(['recovery_code' => 'Kode pemulihan tidak valid.']);
            }

            // Invalidate the used recovery code
            $user->two_factor_recovery_codes = $codes->reject(fn ($c) => $c === $used)->values()->all();
            $user->save();
        } else {
            return back()->withErrors(['code' => 'Masukkan kode verifikasi atau kode pemulihan.']);
        }

        // Complete login
        Auth::loginUsingId($userId);
        $request->session()->forget('two_factor_pending_user_id');
        $request->session()->regenerate();

        // Mark 2FA as confirmed for this session so EnsureTwoFactorAuthenticated
        // middleware allows through all subsequent requests.
        $request->session()->put('two_factor_confirmed', true);

        // Sync tenant session (mirrors AuthenticatedSessionController)
        $tenantId = TenantContext::resolveIdFromUser($request->user())
            ?? TenantContext::resolveIdFromRequest($request);

        TenantContext::setCurrentId($tenantId);
        $tenant = TenantContext::currentTenant();
        $request->session()->put('tenant_id', $tenantId);
        $request->session()->put('tenant_slug', $tenant?->slug);

        if ($request->attributes->get('platform_admin_host')) {
            $request->session()->forget('url.intended');

            return redirect()->route('platform.dashboard');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    // -----------------------------------------------------------------------
    // Setup — user enables 2FA from their profile
    // -----------------------------------------------------------------------

    public function showSetup(Request $request)
    {
        $user = $request->user();

        // Generate a new temp secret if the user hasn't confirmed one yet
        if (! $request->session()->has('two_factor_setup_secret')) {
            $request->session()->put(
                'two_factor_setup_secret',
                $this->google2fa->generateSecretKey()
            );
        }

        $secret = $request->session()->get('two_factor_setup_secret');

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $qrCodeInline = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );

        return view('auth.two-factor-setup', compact('secret', 'qrCodeInline'));
    }

    public function enable(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $secret = $request->session()->get('two_factor_setup_secret');

        if (! $secret) {
            return redirect()->route('two-factor.setup')
                ->withErrors(['code' => 'Sesi setup habis. Silakan ulangi.']);
        }

        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (! $valid) {
            return back()->withErrors(['code' => 'Kode tidak cocok. Pastikan waktu perangkat Anda benar.']);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $request->user()->forceFill([
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        $request->session()->forget('two_factor_setup_secret');
        $request->session()->put('two_factor_recovery_codes_shown', $recoveryCodes);

        return redirect()->route('two-factor.recovery-codes')
            ->with('success', '2FA berhasil diaktifkan!');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $request->user()->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return back()->with('success', '2FA telah dinonaktifkan.');
    }

    // -----------------------------------------------------------------------
    // Recovery codes
    // -----------------------------------------------------------------------

    public function showRecoveryCodes(Request $request)
    {
        // Show newly generated codes once (from session), or the existing encrypted ones
        $codes = $request->session()->pull('two_factor_recovery_codes_shown')
            ?? $request->user()->two_factor_recovery_codes
            ?? [];

        if (empty($codes)) {
            return redirect()->route('profile.edit');
        }

        return view('auth.two-factor-recovery-codes', compact('codes'));
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $codes = $this->generateRecoveryCodes();

        $request->user()->forceFill([
            'two_factor_recovery_codes' => $codes,
        ])->save();

        $request->session()->put('two_factor_recovery_codes_shown', $codes);

        return redirect()->route('two-factor.recovery-codes')
            ->with('success', 'Kode pemulihan baru telah dibuat. Simpan di tempat yang aman.');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function generateRecoveryCodes(): array
    {
        return Collection::times(8, fn () => Str::upper(Str::random(5)) . '-' . Str::upper(Str::random(5)))
            ->all();
    }
}
