<?php

namespace App\Modules\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        // Fetch active sessions if database driver used
        $sessions = [];
        if (config('session.driver') === 'database') {
            $sessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderBy('last_activity', 'desc')
                ->get()
                ->map(function ($session) use ($request) {
                    return [
                        'id' => $session->id,
                        'ip_address' => $session->ip_address,
                        'is_current_device' => $session->id === $request->session()->getId(),
                        'last_active' => date('d.m.Y H:i', $session->last_activity),
                        'user_agent' => $session->user_agent,
                    ];
                });
        }

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_platform_admin' => $user->is_platform_admin,
                'two_factor_enabled' => !empty($user->two_factor_confirmed_at),
            ],
            'sessions' => $sessions,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->fill($validated);
        $user->save();

        AuditLog::log('profile.updated', 'User', $user->id, ['name' => $user->name, 'email' => $user->email]);

        return back()->with('success', 'Profil bilgileriniz güncellendi.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::log('profile.password_changed', 'User', $request->user()->id);

        return back()->with('success', 'Parolanız başarıyla değiştirildi.');
    }

    public function logoutOtherSessions(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($request->password);

        AuditLog::log('profile.other_sessions_terminated', 'User', $request->user()->id);

        return back()->with('success', 'Diğer tüm aktif oturumlar sonlandırıldı.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        AuditLog::log('profile.account_deleted', 'User', $user->id);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
