<?php

namespace App\Modules\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Crawl;
use App\Models\Project;
use App\Models\SeoFinding;
use App\Models\SeoTask;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (!$workspace) {
            return redirect()->route('workspaces.index');
        }

        $projects = Project::where('workspace_id', $workspace->id)
            ->where('is_archived', false)
            ->with(['latestCrawl'])
            ->withCount('tasks')
            ->latest()
            ->take(5)
            ->get();

        $totalProjectsCount = Project::where('workspace_id', $workspace->id)->count();

        $recentCrawls = Crawl::where('workspace_id', $workspace->id)
            ->with('project:id,name,domain')
            ->latest()
            ->take(5)
            ->get();

        $criticalIssuesCount = SeoFinding::where('workspace_id', $workspace->id)
            ->where('severity', 'critical')
            ->count();

        $warningIssuesCount = SeoFinding::where('workspace_id', $workspace->id)
            ->where('severity', 'warning')
            ->count();

        $openTasksCount = SeoTask::where('workspace_id', $workspace->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $totalPagesCrawled = Crawl::where('workspace_id', $workspace->id)
            ->sum('pages_crawled');

        $latestFindings = SeoFinding::where('workspace_id', $workspace->id)
            ->where('severity', 'critical')
            ->with(['page:id,url,title', 'project:id,name,domain'])
            ->latest()
            ->take(6)
            ->get();

        // Calculate average health score from completed crawls
        $avgHealthScore = Crawl::where('workspace_id', $workspace->id)
            ->where('status', 'completed')
            ->whereNotNull('health_score')
            ->avg('health_score');

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'total_projects' => $totalProjectsCount,
                'total_pages_crawled' => (int) $totalPagesCrawled,
                'critical_issues' => $criticalIssuesCount,
                'warning_issues' => $warningIssuesCount,
                'open_tasks' => $openTasksCount,
                'avg_health_score' => $avgHealthScore ? round($avgHealthScore, 1) : null,
            ],
            'projects' => $projects,
            'recentCrawls' => $recentCrawls,
            'criticalFindings' => $latestFindings,
        ]);
    }
}
