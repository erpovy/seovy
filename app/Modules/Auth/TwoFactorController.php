<?php

namespace App\Modules\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    public function enable(Request $request)
    {
        $user = Auth::user();

        // Generate base32 secret and recovery codes
        $secret = strtoupper(Str::random(16));
        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::random(10) . '-' . Str::random(10))->all();

        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->two_factor_confirmed_at = now();
        $user->save();

        AuditLog::log('auth.2fa_enabled', 'User', $user->id);

        return back()->with('success', 'İki faktörlü doğrulama başarıyla etkinleştirildi.');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        AuditLog::log('auth.2fa_disabled', 'User', $user->id);

        return back()->with('success', 'İki faktörlü doğrulama devre dışı bırakıldı.');
    }
}
