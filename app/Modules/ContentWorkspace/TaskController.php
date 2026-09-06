<?php

namespace App\Modules\ContentWorkspace;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\SeoFinding;
use App\Models\SeoTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $query = $project->tasks()->with(['finding', 'assignee']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->latest()->paginate(20)->withQueryString();

        $members = $project->workspace->users()->get(['users.id', 'users.name']);

        return Inertia::render('Tasks/Index', [
            'project' => $project,
            'tasks' => $tasks,
            'members' => $members,
            'filters' => $request->only(['status', 'priority']),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'seo_finding_id' => ['nullable', 'exists:seo_findings,id'],
        ]);

        $task = SeoTask::create([
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
            'seo_finding_id' => $validated['seo_finding_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'status' => 'open',
            'assigned_to' => $validated['assigned_to'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        AuditLog::log('task.created', 'SeoTask', $task->id, ['title' => $task->title]);

        return back()->with('success', 'SEO görevi başarıyla oluşturuldu.');
    }

    /**
     * Convert an SEO Finding directly into a Task.
     */
    public function convertFromFinding(Request $request, Project $project, SeoFinding $finding)
    {
        Gate::authorize('update', $project);

        $priorityMap = [
            'critical' => 'critical',
            'warning' => 'high',
            'notice' => 'medium',
        ];

        $task = SeoTask::create([
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
            'seo_finding_id' => $finding->id,
            'title' => $finding->title,
            'description' => "Öneri: {$finding->recommendation}\n\nDetay: {$finding->description}",
            'priority' => $priorityMap[$finding->severity] ?? 'medium',
            'status' => 'open',
        ]);

        AuditLog::log('task.converted_from_finding', 'SeoTask', $task->id, [
            'finding_id' => $finding->id,
            'rule_code' => $finding->rule_code,
        ]);

        return back()->with('success', 'Bulgu göreve dönüştürüldü.');
    }

    public function update(Request $request, Project $project, SeoTask $task)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high,critical'],
            'status' => ['sometimes', 'in:open,in_progress,completed,ignored'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task->update($validated);

        AuditLog::log('task.updated', 'SeoTask', $task->id, $validated);

        return back()->with('success', 'Görev güncellendi.');
    }

    public function destroy(Project $project, SeoTask $task)
    {
        Gate::authorize('update', $project);

        AuditLog::log('task.deleted', 'SeoTask', $task->id, ['title' => $task->title]);

        $task->delete();

        return back()->with('success', 'Görev silindi.');
    }
}
