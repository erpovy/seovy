<?php

namespace App\Modules\Security;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Crawler\SafeHttpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class Php2ShellController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Project::class);
        $projects = Project::where('workspace_id', $request->user()->current_workspace_id)
            ->where('is_archived', false)->orderBy('name')->get(['id', 'name', 'domain', 'start_url', 'workspace_id']);
        $selected = $projects->firstWhere('id', (int) $request->query('project'));

        return Inertia::render('Security/Php2Shell', [
            'projects' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'domain' => $project->domain,
                'can_scan' => $request->user()->can('crawl', $project),
            ]),
            'selectedProjectId' => $selected?->id,
            'assessment' => $selected ? Cache::get($this->cacheKey($selected)) : null,
        ]);
    }

    public function scan(Request $request, Project $project, SafeHttpClient $http, Php2ShellDetector $detector)
    {
        Gate::authorize('crawl', $project);
        abort_unless($project->workspace_id === $request->user()->current_workspace_id && ! $project->is_archived, 404);

        // A failed recheck must not leave an earlier result looking current.
        Cache::forget($this->cacheKey($project));
        try {
            $response = $http->get($project->start_url);
        } catch (\Throwable $exception) {
            return redirect()->route('security.php2shell', ['project' => $project->id])
                ->withErrors(['scan' => 'Hedef kontrol edilemedi. Proje adresini ve internetten erişilebilirliğini doğrulayıp tekrar deneyin.']);
        }

        Cache::put($this->cacheKey($project), $detector->report($response), now()->addDay());

        return redirect()->route('security.php2shell', ['project' => $project->id]);
    }

    private function cacheKey(Project $project): string
    {
        return "php2shell:v1:{$project->workspace_id}:{$project->id}:".hash('sha256', $project->start_url);
    }
}
