<?php

namespace App\Modules\Workspace;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        Gate::authorize('view', $workspace);

        $members = $workspace->users()->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
                'is_owner' => $user->id === $user->pivot->workspace_id,
            ];
        });

        $invitations = $workspace->invitations()
            ->where('expires_at', '>', now())
            ->get();

        return Inertia::render('Workspace/Members', [
            'workspace' => $workspace,
            'members' => $members,
            'invitations' => $invitations,
            'canManage' => $request->user()->hasRole($workspace, ['owner', 'admin']),
        ]);
    }

    public function invite(Request $request, Workspace $workspace)
    {
        Gate::authorize('invite', $workspace);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:admin,specialist,viewer'],
        ]);

        // Check if user is already a member
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser && $workspace->users()->where('users.id', $existingUser->id)->exists()) {
            return back()->withErrors(['email' => 'Bu kullanıcı zaten bu çalışma alanının üyesidir.']);
        }

        // Delete existing pending invitation if any
        WorkspaceInvitation::where('workspace_id', $workspace->id)
            ->where('email', $validated['email'])
            ->delete();

        $token = Str::random(40);
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        AuditLog::log('workspace.invitation_sent', 'Workspace', $workspace->id, [
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        return back()->with('success', "{$validated['email']} adresine davet bağlantısı oluşturuldu.");
    }

    public function acceptInvitation(Request $request, string $token)
    {
        $invitation = WorkspaceInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect('/login')->withErrors(['error' => 'Bu davet bağlantısının süresi dolmuş.']);
        }

        $user = Auth::user();
        if (!$user) {
            // Store token in session and redirect to register or login
            session(['invitation_token' => $token]);
            return redirect()->route('register', ['email' => $invitation->email]);
        }

        $workspace = $invitation->workspace;

        if (!$workspace->users()->where('users.id', $user->id)->exists()) {
            $workspace->users()->attach($user->id, ['role' => $invitation->role]);
        }

        $invitation->delete();

        $user->current_workspace_id = $workspace->id;
        $user->save();

        AuditLog::log('workspace.invitation_accepted', 'Workspace', $workspace->id, [
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        return redirect()->route('dashboard')->with('success', "{$workspace->name} çalışma alanına katıldınız!");
    }

    public function updateRole(Request $request, Workspace $workspace, User $user)
    {
        Gate::authorize('manageMembers', $workspace);

        $validated = $request->validate([
            'role' => ['required', 'in:admin,specialist,viewer'],
        ]);

        if ($workspace->owner_id === $user->id) {
            return back()->withErrors(['error' => 'Çalışma alanı sahibinin rolü değiştirilemez.']);
        }

        $workspace->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        AuditLog::log('workspace.role_updated', 'Workspace', $workspace->id, [
            'target_user_id' => $user->id,
            'new_role' => $validated['role'],
        ]);

        return back()->with('success', 'Kullanıcı rolü güncellendi.');
    }

    public function removeMember(Request $request, Workspace $workspace, User $user)
    {
        Gate::authorize('manageMembers', $workspace);

        if ($workspace->owner_id === $user->id) {
            return back()->withErrors(['error' => 'Çalışma alanı sahibi ekipten çıkarılamaz.']);
        }

        $workspace->users()->detach($user->id);

        if ($user->current_workspace_id === $workspace->id) {
            $fallback = $user->workspaces()->first();
            $user->current_workspace_id = $fallback ? $fallback->id : null;
            $user->save();
        }

        AuditLog::log('workspace.member_removed', 'Workspace', $workspace->id, [
            'target_user_id' => $user->id,
        ]);

        return back()->with('success', 'Kullanıcı çalışma alanından çıkarıldı.');
    }
}
