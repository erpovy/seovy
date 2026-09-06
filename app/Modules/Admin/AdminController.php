<?php

namespace App\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crawl;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->authorizeAdmin($request);

        // System Infrastructure Health Check
        $dbOk = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        $redisOk = true;
        try {
            if (config('cache.default') === 'redis' || config('queue.default') === 'redis') {
                Redis::ping();
            }
        } catch (\Throwable $e) {
            $redisOk = false;
        }

        $failedJobsCount = DB::table('failed_jobs')->count();
        $recentAuditLogs = AuditLog::with('user:id,name,email')->latest()->take(25)->get();

        $stats = [
            'total_users' => User::count(),
            'total_workspaces' => Workspace::count(),
            'total_projects' => Project::count(),
            'total_crawls' => Crawl::count(),
            'failed_jobs' => $failedJobsCount,
            'db_status' => $dbOk,
            'redis_status' => $redisOk,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        // Query all registered users with their active workspace, all workspaces, and tracked websites
        $usersQuery = User::with([
            'currentWorkspace:id,name,slug,owner_id,is_active',
            'workspaces' => function ($q) {
                $q->select('workspaces.id', 'workspaces.name', 'workspaces.slug', 'workspaces.owner_id', 'workspaces.is_active')
                  ->with([
                      'projects' => function ($pq) {
                          $pq->select('projects.id', 'projects.workspace_id', 'projects.name', 'projects.domain', 'projects.start_url', 'projects.target_country', 'projects.target_language', 'projects.created_at')
                             ->withCount(['crawls', 'keywords'])
                             ->with(['latestCrawl:id,project_id,status,health_score,pages_crawled,created_at']);
                      }
                  ]);
            }
        ])->latest();

        // Search filter: user name, email, workspace name, or project domain/name
        if ($request->filled('search')) {
            $search = trim($request->search);
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('workspaces', function ($wq) use ($search) {
                      $wq->where('name', 'like', "%{$search}%")
                         ->orWhereHas('projects', function ($pq) use ($search) {
                             $pq->where('domain', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                         });
                  });
            });
        }

        // Role / Status filter
        if ($request->filled('filter')) {
            $filter = $request->filter;
            if ($filter === 'admin') {
                $usersQuery->where('is_platform_admin', true);
            } elseif ($filter === 'has_sites') {
                $usersQuery->whereHas('workspaces.projects');
            } elseif ($filter === 'no_sites') {
                $usersQuery->whereDoesntHave('workspaces.projects');
            }
        }

        $users = $usersQuery->paginate(20)->withQueryString();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentLogs' => $recentAuditLogs,
            'users' => $users,
            'filters' => [
                'search' => $request->search ?? '',
                'filter' => $request->filter ?? 'all',
                'tab' => $request->tab ?? 'users',
            ],
        ]);
    }

    public function users(Request $request)
    {
        return redirect()->route('admin.dashboard', array_merge(['tab' => 'users'], $request->query()));
    }

    public function workspaces(Request $request)
    {
        return redirect()->route('admin.dashboard', array_merge(['tab' => 'system'], $request->query()));
    }

    public function auditLogs(Request $request)
    {
        return redirect()->route('admin.dashboard', array_merge(['tab' => 'logs'], $request->query()));
    }

    protected function authorizeAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->is_platform_admin) {
            abort(403, 'Bu sayfaya erişim için platform yöneticisi yetkisi gereklidir.');
        }
    }
}
