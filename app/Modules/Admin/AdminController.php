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

        // System Health Check
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
        $recentAuditLogs = AuditLog::with('user:id,name,email')->latest()->take(10)->get();

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

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentLogs' => $recentAuditLogs,
        ]);
    }

    public function users(Request $request)
    {
        $this->authorizeAdmin($request);

        $users = User::withCount('workspaces')->latest()->paginate(25);

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    public function workspaces(Request $request)
    {
        $this->authorizeAdmin($request);

        $workspaces = Workspace::with('owner:id,name,email')
            ->withCount(['users', 'projects'])
            ->latest()
            ->paginate(25);

        return Inertia::render('Admin/Workspaces', [
            'workspaces' => $workspaces,
        ]);
    }

    public function auditLogs(Request $request)
    {
        $this->authorizeAdmin($request);

        $logs = AuditLog::with(['user:id,name,email', 'workspace:id,name'])
            ->latest()
            ->paginate(50);

        return Inertia::render('Admin/AuditLogs', [
            'logs' => $logs,
        ]);
    }

    protected function authorizeAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->is_platform_admin) {
            abort(403, 'Bu sayfaya erişim için platform yöneticisi yetkisi gereklidir.');
        }
    }
}
