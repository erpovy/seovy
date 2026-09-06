<?php

namespace App\Modules\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Ensure user has a current workspace
            if (!$user->current_workspace_id) {
                $firstWorkspace = $user->workspaces()->first();
                if ($firstWorkspace) {
                    $user->current_workspace_id = $firstWorkspace->id;
                    $user->save();
                }
            }

            AuditLog::log('auth.login', 'User', $user->id, ['email' => $user->email]);

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Girdiğiniz bilgiler kayıtlarımızla eşleşmiyor.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Automatically create a personal workspace for the new user
        $workspaceName = $request->name . ' Çalışma Alanı';
        $workspace = Workspace::create([
            'name' => $workspaceName,
            'slug' => Str::slug($workspaceName) . '-' . Str::random(5),
            'owner_id' => $user->id,
            'personal_team' => true,
            'is_active' => true,
        ]);

        // Attach user as owner
        $workspace->users()->attach($user->id, ['role' => 'owner']);

        // Create default subscription/limits
        Subscription::create([
            'workspace_id' => $workspace->id,
            'plan_name' => 'free',
            'status' => 'active',
            'limits' => [
                'max_projects' => 1,
                'max_pages_monthly' => 500,
                'max_keywords' => 10,
                'team_members' => 1,
            ],
        ]);

        $user->current_workspace_id = $workspace->id;
        $user->save();

        Auth::login($user);

        AuditLog::log('auth.register', 'User', $user->id, [
            'workspace_id' => $workspace->id,
            'email' => $user->email,
        ]);

        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        AuditLog::log('auth.logout', 'User', $userId);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
