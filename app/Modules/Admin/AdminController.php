<?php

namespace App\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crawl;
use App\Models\PaymentGateway;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Modules\Billing\Services\PaymentSimulationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function __construct(
        protected PaymentSimulationService $simulationService
    ) {}

    public function dashboard(Request $request)
    {
        $this->authorizeAdmin($request);

        // Ensure default gateways & settings exist
        $this->simulationService->ensureInitialized();

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
        $recentAuditLogs = AuditLog::with(['user:id,name,email', 'workspace:id,name'])->latest()->take(50)->get();

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
            'total_payment_volume' => (float) PaymentTransaction::where('status', 'success')->sum('amount'),
            'total_payment_transactions' => PaymentTransaction::count(),
            'simulated_transactions' => PaymentTransaction::where('is_simulation', true)->count(),
            'active_gateways_count' => PaymentGateway::where('is_active', true)->count(),
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
                             ->with(['latestCrawl']);
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

        // Payment Gateways & Transactions
        $gateways = PaymentGateway::orderBy('sort_order')->get();
        $paymentSettings = [
            'test_mode' => (bool) PaymentSetting::get('test_mode', true),
            'default_gateway' => (string) PaymentSetting::get('default_gateway', 'iyzico'),
        ];
        $recentTransactions = PaymentTransaction::with(['workspace:id,name', 'user:id,name,email'])
            ->latest()
            ->take(50)
            ->get();
        $workspacesList = Workspace::select('id', 'name', 'owner_id')
            ->with('owner:id,name,email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentLogs' => $recentAuditLogs,
            'users' => $users,
            'gateways' => $gateways,
            'paymentSettings' => $paymentSettings,
            'recentTransactions' => $recentTransactions,
            'workspacesList' => $workspacesList,
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

    public function payments(Request $request)
    {
        return redirect()->route('admin.dashboard', array_merge(['tab' => 'payments'], $request->query()));
    }

    /**
     * Update Virtual POS Gateway configuration.
     */
    public function updateGateway(Request $request, PaymentGateway $gateway)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'mode' => ['required', 'in:test,live'],
            'currency' => ['nullable', 'string', 'max:10'],
            'credentials' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ]);

        $gateway->update($validated);

        AuditLog::log('payment_gateway.updated', 'PaymentGateway', $gateway->id, [
            'gateway' => $gateway->name,
            'is_active' => $gateway->is_active,
            'mode' => $gateway->mode,
        ]);

        return back()->with('success', "{$gateway->name} sanal POS ayarları güncellendi.");
    }

    /**
     * Update global payment settings (e.g. Test / Simulation Mode toggle).
     */
    public function updatePaymentSettings(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'test_mode' => ['required', 'boolean'],
            'default_gateway' => ['nullable', 'string'],
        ]);

        PaymentSetting::set('test_mode', $validated['test_mode']);
        if (!empty($validated['default_gateway'])) {
            PaymentSetting::set('default_gateway', $validated['default_gateway']);
        }

        AuditLog::log('payment_settings.updated', 'PaymentSetting', 0, [
            'test_mode' => $validated['test_mode'] ? 'ACTIVE' : 'DISABLED',
            'default_gateway' => $validated['default_gateway'] ?? 'none',
        ]);

        $msg = $validated['test_mode']
            ? 'Test / Simülasyon Modu AKTİF edildi. Satın alma işlemleri simüle edilecektir.'
            : 'Test modu kapatıldı. Canlı Sanal POS modu aktif.';

        return back()->with('success', $msg);
    }

    /**
     * Trigger a purchase simulation.
     */
    public function simulatePayment(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'workspace_id' => ['nullable', 'exists:workspaces,id'],
            'gateway_code' => ['required', 'string'],
            'plan_name' => ['required', 'string', 'in:free,pro,agency,custom'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'scenario' => ['required', 'string', 'in:success,3ds_success,insufficient_funds,bank_declined'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'card_brand' => ['nullable', 'string', 'max:50'],
        ]);

        $transaction = $this->simulationService->simulatePayment($validated, $request->user());

        $statusMsg = $transaction->status === 'success'
            ? "Simülasyon BAŞARILI: {$transaction->transaction_id} numaralı işlem onaylandı ve paket güncellendi."
            : "Simülasyon TAMAMLANDI (Banka Reddi): {$transaction->error_message}";

        return back()->with('success', $statusMsg);
    }

    /**
     * Simulate a refund for a transaction.
     */
    public function simulateRefund(Request $request, PaymentTransaction $transaction)
    {
        $this->authorizeAdmin($request);

        $this->simulationService->simulateRefund($transaction, $request->user());

        return back()->with('success', "{$transaction->transaction_id} nolu işlem için iade simülasyonu yapıldı.");
    }

    protected function authorizeAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->is_platform_admin) {
            abort(403, 'Bu sayfaya erişim için platform yöneticisi yetkisi gereklidir.');
        }
    }
}
