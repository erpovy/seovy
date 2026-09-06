<?php

namespace App\Modules\Workspace;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $workspaces = $user->workspaces()->withCount(['projects', 'users'])->get();

        return Inertia::render('Workspace/Index', [
            'workspaces' => $workspaces,
            'currentWorkspace' => $user->currentWorkspace,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $workspace = Workspace::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(5),
            'owner_id' => $user->id,
            'personal_team' => false,
            'is_active' => true,
        ]);

        $workspace->users()->attach($user->id, ['role' => 'owner']);

        // Default subscription
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

        AuditLog::log('workspace.created', 'Workspace', $workspace->id, ['name' => $workspace->name]);

        return redirect()->route('dashboard')->with('success', 'Çalışma alanı başarıyla oluşturuldu.');
    }

    public function switch(Request $request, Workspace $workspace)
    {
        $user = $request->user();

        if (!$user->workspaces()->where('workspaces.id', $workspace->id)->exists() && !$user->is_platform_admin) {
            abort(403, 'Bu çalışma alanına erişim izniniz yok.');
        }

        $user->current_workspace_id = $workspace->id;
        $user->save();

        AuditLog::log('workspace.switched', 'Workspace', $workspace->id);

        return back()->with('success', 'Çalışma alanı değiştirildi: ' . $workspace->name);
    }

    public function update(Request $request, Workspace $workspace)
    {
        Gate::authorize('update', $workspace);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'retention_days' => ['required', 'integer', 'min:7', 'max:365'],
        ]);

        $workspace->update($validated);

        AuditLog::log('workspace.updated', 'Workspace', $workspace->id, $validated);

        return back()->with('success', 'Çalışma alanı ayarları güncellendi.');
    }

    public function destroy(Request $request, Workspace $workspace)
    {
        Gate::authorize('delete', $workspace);

        $user = $request->user();

        if ($workspace->personal_team) {
            return back()->withErrors(['error' => 'Kişisel çalışma alanınızı silemezsiniz.']);
        }

        AuditLog::log('workspace.deleted', 'Workspace', $workspace->id, ['name' => $workspace->name]);

        $workspace->delete();

        // Switch to user's first available workspace
        $fallback = $user->workspaces()->first();
        $user->current_workspace_id = $fallback ? $fallback->id : null;
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Çalışma alanı silindi.');
    }

    public function exportData(Request $request, Workspace $workspace)
    {
        Gate::authorize('view', $workspace);

        $data = [
            'workspace' => $workspace->only(['id', 'name', 'slug', 'created_at']),
            'projects' => $workspace->projects()->with(['tasks', 'keywords'])->get(),
            'exported_at' => now()->toIso8601String(),
        ];

        AuditLog::log('workspace.exported', 'Workspace', $workspace->id);

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, "workspace-{$workspace->slug}-export-" . date('Y-m-d') . ".json");
    }
}
