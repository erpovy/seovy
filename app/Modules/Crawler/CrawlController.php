<?php

namespace App\Modules\Crawler;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCrawlJob;
use App\Models\Crawl;
use App\Models\Project;
use App\Models\SeoFinding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CrawlController extends Controller
{
    public function __construct(protected CrawlService $crawlService) {}

    public function start(Request $request, Project $project)
    {
        Gate::authorize('crawl', $project);

        // Check if there is already an active crawl running for this project
        $activeCrawl = $project->crawls()->where('status', 'running')->first();
        if ($activeCrawl) {
            return back()->withErrors(['error' => 'Bu proje için zaten devam eden aktif bir tarama bulunmaktadır.']);
        }

        // If there's an abandoned pending crawl older than 5 minutes, mark it as cancelled
        $project->crawls()->where('status', 'pending')->where('created_at', '<', now()->subMinutes(5))->update(['status' => 'cancelled']);

        // Check again
        $stuckPending = $project->crawls()->where('status', 'pending')->first();
        if ($stuckPending && config('queue.default') === 'sync') {
            // Immediately execute the pending crawl directly
            $this->crawlService->executeCrawl($stuckPending);
            return redirect()->route('crawls.show', [$project->id, $stuckPending->id])
                ->with('success', 'Bekleyen tarama tamamlandı.');
        }

        // SaaS limit check
        $workspace = $project->workspace;
        $subscription = $workspace->subscription;
        if (config('app.install_mode') === 'saas' && $subscription) {
            $limits = $subscription->getLimits();
            $currentMonthPages = Crawl::where('workspace_id', $workspace->id)
                ->whereMonth('created_at', now()->month)
                ->sum('pages_crawled');

            if ($currentMonthPages >= $limits['max_pages_monthly']) {
                return back()->withErrors(['error' => "Aylık taranan sayfa kotanızı ({$limits['max_pages_monthly']}) aştınız. Lütfen paketinizi yükseltin."]);
            }
        }

        $validated = $request->validate([
            'max_pages' => ['nullable', 'integer', 'min:5', 'max:5000'],
            'max_depth' => ['nullable', 'integer', 'min:1', 'max:8'],
        ]);

        $crawl = $this->crawlService->startCrawl(
            $project,
            $validated['max_pages'] ?? null,
            $validated['max_depth'] ?? null
        );

        // If queue connection is sync or request asks for direct, execute inline
        if (config('queue.default') === 'sync') {
            $this->crawlService->executeCrawl($crawl);
        } else {
            // Dispatch background queue job
            ProcessCrawlJob::dispatch($crawl);
        }

        return redirect()->route('crawls.show', [$project->id, $crawl->id])
            ->with('success', 'Tarama işlemi başlatıldı.');
    }

    public function show(Request $request, Project $project, Crawl $crawl)
    {
        Gate::authorize('view', $project);

        $crawl->loadCount(['pages', 'findings']);

        // Findings query with category and severity filters
        $findingsQuery = $crawl->findings()->with('page:id,url,title');

        if ($request->filled('severity')) {
            $findingsQuery->where('severity', $request->severity);
        }

        if ($request->filled('category')) {
            $findingsQuery->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $findingsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('rule_code', 'like', "%{$search}%");
            });
        }

        $findings = $findingsQuery->paginate(20)->withQueryString();

        // Pages query
        $pagesQuery = $crawl->pages();
        if ($request->filled('status_code')) {
            $pagesQuery->where('status_code', $request->status_code);
        }
        $pages = $pagesQuery->paginate(15, ['*'], 'pages_page')->withQueryString();

        // Severity summary counts
        $severityCounts = [
            'critical' => $crawl->findings()->where('severity', 'critical')->count(),
            'warning' => $crawl->findings()->where('severity', 'warning')->count(),
            'notice' => $crawl->findings()->where('severity', 'notice')->count(),
        ];

        return Inertia::render('Crawls/Show', [
            'project' => $project,
            'crawl' => $crawl,
            'findings' => $findings,
            'pages' => $pages,
            'severityCounts' => $severityCounts,
            'filters' => $request->only(['severity', 'category', 'search', 'status_code']),
        ]);
    }

    /**
     * Polling endpoint for real-time live progress updates without refreshing.
     */
    public function status(Project $project, Crawl $crawl)
    {
        Gate::authorize('view', $project);

        return response()->json([
            'status' => $crawl->status,
            'pages_crawled' => $crawl->pages_crawled,
            'pages_discovered' => $crawl->pages_discovered,
            'duration_seconds' => $crawl->duration_seconds,
            'health_score' => $crawl->health_score,
            'completed_at' => $crawl->completed_at?->toIso8601String(),
        ]);
    }

    public function pause(Project $project, Crawl $crawl)
    {
        Gate::authorize('crawl', $project);

        $this->crawlService->pause($crawl);

        return back()->with('info', 'Tarama duraklatıldı.');
    }

    public function cancel(Project $project, Crawl $crawl)
    {
        Gate::authorize('crawl', $project);

        $this->crawlService->cancel($crawl);

        return back()->with('info', 'Tarama iptal edildi.');
    }

    /**
     * Compare two crawls: New vs Resolved vs Recurring issues.
     */
    public function compare(Request $request, Project $project, Crawl $crawl1, Crawl $crawl2)
    {
        Gate::authorize('view', $project);

        // crawl1 = older, crawl2 = newer
        if ($crawl1->created_at > $crawl2->created_at) {
            [$crawl1, $crawl2] = [$crawl2, $crawl1];
        }

        $crawl1Findings = $crawl1->findings()->pluck('rule_code')->toArray();
        $crawl2Findings = $crawl2->findings()->pluck('rule_code')->toArray();

        $resolvedIssues = array_values(array_diff($crawl1Findings, $crawl2Findings));
        $newIssues = array_values(array_diff($crawl2Findings, $crawl1Findings));
        $recurringIssues = array_values(array_intersect($crawl1Findings, $crawl2Findings));

        return Inertia::render('Crawls/Compare', [
            'project' => $project,
            'olderCrawl' => $crawl1,
            'newerCrawl' => $crawl2,
            'diff' => [
                'resolved_count' => count($resolvedIssues),
                'new_count' => count($newIssues),
                'recurring_count' => count($recurringIssues),
                'resolved' => $resolvedIssues,
                'new' => $newIssues,
                'recurring' => $recurringIssues,
            ],
        ]);
    }
}
