<?php

namespace App\Modules\Integrations;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Integration;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    public function index(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $integrations = $project->integrations()->get()->keyBy('type');

        $pageSpeedService = new PageSpeedService();

        return Inertia::render('Integrations/Index', [
            'project' => $project,
            'integrations' => [
                'gsc' => $integrations->get('gsc'),
                'ga4' => $integrations->get('ga4'),
                'pagespeed' => $integrations->get('pagespeed'),
            ],
            'pagespeedConfigured' => $pageSpeedService->isConfigured(),
        ]);
    }

    public function runPageSpeed(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $url = $request->input('url', $project->start_url);
        $service = new PageSpeedService();
        $result = $service->audit($url);

        // Store result in integrations table
        Integration::updateOrCreate(
            ['project_id' => $project->id, 'type' => 'pagespeed'],
            [
                'workspace_id' => $project->workspace_id,
                'settings' => $result,
                'is_active' => true,
                'last_synced_at' => now(),
            ]
        );

        AuditLog::log('integration.pagespeed_run', 'Project', $project->id, ['url' => $url]);

        return back()->with('success', 'PageSpeed ölçümü tamamlandı.');
    }

    public function saveCredentials(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'type' => ['required', 'in:gsc,ga4,pagespeed,serp_provider'],
            'credentials' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ]);

        Integration::updateOrCreate(
            ['project_id' => $project->id, 'type' => $validated['type']],
            [
                'workspace_id' => $project->workspace_id,
                'credentials' => $validated['credentials'] ?? null,
                'settings' => $validated['settings'] ?? null,
                'is_active' => true,
                'last_synced_at' => now(),
            ]
        );

        AuditLog::log('integration.updated', 'Project', $project->id, ['type' => $validated['type']]);

        return back()->with('success', 'Entegrasyon ayarları şifrelenerek kaydedildi.');
    }

    public function disconnect(Request $request, Project $project, string $type)
    {
        Gate::authorize('update', $project);

        $project->integrations()->where('type', $type)->delete();

        AuditLog::log('integration.disconnected', 'Project', $project->id, ['type' => $type]);

        return back()->with('success', 'Entegrasyon bağlantısı kaldırıldı.');
    }
}
