<?php

namespace App\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crawl;
use App\Models\PaymentGateway;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Project;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Workspace;
use App\Modules\Billing\Services\PaymentSimulationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function __construct(
        protected PaymentSimulationService $simulationService
    ) {}

    public function dashboard(Request $request)
    {
        $this->authorizeAdmin($request);

        // Ensure default gateways, settings, and plans exist
        $this->simulationService->ensureInitialized();
        SubscriptionPlan::ensureDefaultPlans();

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
            'total_plans_count' => SubscriptionPlan::count(),
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

        // Payment Gateways & Settings
        $gateways = PaymentGateway::orderBy('sort_order')->get();
        $paymentSettings = [
            'test_mode' => (bool) PaymentSetting::get('test_mode', true),
            'default_gateway' => (string) PaymentSetting::get('default_gateway', 'iyzico'),
        ];

        // Subscription Plans
        $plansList = SubscriptionPlan::orderBy('sort_order')->get();

        // Transactions & Sales Logs (with search and filters)
        $salesQuery = PaymentTransaction::with(['workspace:id,name,slug', 'user:id,name,email'])
            ->latest();

        if ($request->filled('sales_search')) {
            $s = trim($request->sales_search);
            $salesQuery->where(function ($q) use ($s) {
                $q->where('transaction_id', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_email', 'like', "%{$s}%")
                  ->orWhereHas('workspace', function ($wq) use ($s) {
                      $wq->where('name', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('sales_plan') && $request->sales_plan !== 'all') {
            $salesQuery->where('plan_name', $request->sales_plan);
        }

        if ($request->filled('sales_mode') && $request->sales_mode !== 'all') {
            $salesQuery->where('is_simulation', $request->sales_mode === 'simulation');
        }

        if ($request->filled('sales_status') && $request->sales_status !== 'all') {
            $salesQuery->where('status', $request->sales_status);
        }

        $recentTransactions = $salesQuery->take(100)->get();

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
            'plansList' => $plansList,
            'recentTransactions' => $recentTransactions,
            'workspacesList' => $workspacesList,
            'systemSettings' => [
                'logo' => SystemSetting::get('system_logo_dark') ?: SystemSetting::get('system_logo', null),
                'logo_dark' => SystemSetting::get('system_logo_dark') ?: SystemSetting::get('system_logo', null),
                'logo_light' => SystemSetting::get('system_logo_light', null),
                'favicon' => SystemSetting::get('system_favicon', null),
                'brand_name' => SystemSetting::get('brand_name', 'Seovy'),
                'features_badge' => SystemSetting::get('features_page_badge', 'Platform Özellikleri & Mimarisi'),
                'features_title' => SystemSetting::get('features_page_title', 'Teknik SEO & Analiz Altyapısı'),
                'features_subtitle' => SystemSetting::get('features_page_subtitle', 'Kendi sunucunuzda çalışan, çoklu çalışma alanları, SSRF korumalı crawler ve 25+ teknik analiz kuralı içeren kurumsal platform.'),
                'features_list' => SystemSetting::get('features_page_list', SystemSetting::getDefaultFeatures()),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'filter' => $request->filter ?? 'all',
                'tab' => $request->tab ?? 'users',
                'sales_search' => $request->sales_search ?? '',
                'sales_plan' => $request->sales_plan ?? 'all',
                'sales_mode' => $request->sales_mode ?? 'all',
                'sales_status' => $request->sales_status ?? 'all',
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

    public function plans(Request $request)
    {
        return redirect()->route('admin.dashboard', array_merge(['tab' => 'plans'], $request->query()));
    }

    public function sales(Request $request)
    {
        return redirect()->route('admin.dashboard', array_merge(['tab' => 'sales'], $request->query()));
    }

    /**
     * Create a new subscription plan.
     */
    public function storePlan(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:subscription_plans,code'],
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
            'limits.max_projects' => ['nullable', 'integer', 'min:1'],
            'limits.max_pages_monthly' => ['nullable', 'integer', 'min:10'],
            'limits.max_keywords' => ['nullable', 'integer', 'min:0'],
            'limits.team_members' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'is_popular' => ['nullable', 'boolean'],
        ]);

        $sortOrder = SubscriptionPlan::max('sort_order') + 1;
        $validated['sort_order'] = $sortOrder;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['is_popular'] = $validated['is_popular'] ?? false;

        $plan = SubscriptionPlan::create($validated);

        AuditLog::log('plan.created', 'SubscriptionPlan', $plan->id, [
            'name' => $plan->name,
            'price' => "{$plan->price} {$plan->currency}",
            'limits' => $plan->limits,
        ]);

        return back()->with('success', "{$plan->name} başarıyla oluşturuldu.");
    }

    /**
     * Update an existing subscription plan's pricing, features, and quotas.
     */
    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
            'limits.max_projects' => ['nullable', 'integer', 'min:1'],
            'limits.max_pages_monthly' => ['nullable', 'integer', 'min:10'],
            'limits.max_keywords' => ['nullable', 'integer', 'min:0'],
            'limits.team_members' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'is_popular' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $plan->update($validated);

        AuditLog::log('plan.updated', 'SubscriptionPlan', $plan->id, [
            'name' => $plan->name,
            'price' => "{$plan->price} {$plan->currency}",
            'limits' => $plan->limits,
        ]);

        return back()->with('success', "{$plan->name} fiyat ve kota ayarları güncellendi.");
    }

    /**
     * Delete a custom plan (default plans cannot be deleted).
     */
    public function destroyPlan(Request $request, SubscriptionPlan $plan)
    {
        $this->authorizeAdmin($request);

        if (in_array($plan->code, ['free', 'pro', 'agency'])) {
            return back()->withErrors(['error' => 'Varsayılan sistem planları silinemez, dilerseniz pasif duruma getirebilirsiniz.']);
        }

        $name = $plan->name;
        $plan->delete();

        AuditLog::log('plan.deleted', 'SubscriptionPlan', $plan->id, ['name' => $name]);

        return back()->with('success', "{$name} planı silindi.");
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
            'plan_name' => ['required', 'string'],
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

    /**
     * Update system branding and logo.
     */
    public function updateLogo(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('AdminController@updateLogo called', [
            'user' => $request->user()?->only(['id', 'email', 'is_platform_admin']),
            'inputs' => $request->except(['logo_dark_file', 'logo_light_file', 'logo_file', 'favicon_file']),
            'files' => array_map(fn($f) => ['name' => $f->getClientOriginalName(), 'size' => $f->getSize(), 'mime' => $f->getMimeType()], $request->allFiles()),
        ]);

        $this->authorizeAdmin($request);

        if ($request->input('action') === 'reset') {
            SystemSetting::set('system_logo', null);
            SystemSetting::set('system_logo_dark', null);
            SystemSetting::set('system_logo_light', null);
            SystemSetting::set('system_favicon', null);
            SystemSetting::set('brand_name', 'Seovy');

            AuditLog::log('system_settings.logo_reset', 'SystemSetting', 0, [
                'action' => 'reset_to_default',
            ]);

            return back()->with('success', 'Sistem logoları, favicon ve marka adı varsayılana sıfırlandı.');
        }

        // Check if upload errors occurred due to php.ini limits (upload_max_filesize)
        foreach (['logo_dark_file', 'logo_light_file', 'logo_file', 'favicon_file'] as $key) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_INI_SIZE) {
                return back()->withErrors([$key => 'Yüklenen dosya sunucu boyut sınırını (2 MB) aşıyor. Lütfen daha küçük bir dosya seçin veya SVG formatı kullanın.']);
            }
        }

        // Filter out non-file strings ('null', '', etc.) for file inputs to prevent validation issues
        foreach (['logo_dark_file', 'logo_light_file', 'logo_file', 'favicon_file'] as $fileKey) {
            if (!$request->hasFile($fileKey)) {
                $request->request->remove($fileKey);
            }
        }

        $validated = $request->validate([
            'logo_dark_file' => ['nullable', 'file', 'max:5120'],
            'logo_dark_url' => ['nullable', 'string', 'max:500'],
            'logo_light_file' => ['nullable', 'file', 'max:5120'],
            'logo_light_url' => ['nullable', 'string', 'max:500'],
            // Backward-compatible generic logo input
            'logo_file' => ['nullable', 'file', 'max:5120'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'favicon_file' => ['nullable', 'file', 'max:2048'],
            'favicon_url' => ['nullable', 'string', 'max:500'],
            'brand_name' => ['nullable', 'string', 'max:50'],
        ]);

        $destinationPath = public_path('uploads/branding');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $allowedImageExts = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
        $allowedFaviconExts = ['ico', 'png', 'svg', 'webp', 'jpg', 'jpeg'];

        // Dark theme logo handling
        if ($request->hasFile('logo_dark_file')) {
            $file = $request->file('logo_dark_file');
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowedImageExts, true)) {
                return back()->withErrors(['logo_dark_file' => 'Karanlık tema logosu desteklenen formatta olmalıdır: PNG, JPG, JPEG, SVG, WebP.']);
            }
            $filename = 'logo_dark_' . time() . '_' . Str::random(6) . '.' . $ext;
            $file->move($destinationPath, $filename);
            $logoPath = '/uploads/branding/' . $filename;
            SystemSetting::set('system_logo_dark', $logoPath);
            SystemSetting::set('system_logo', $logoPath);
        } elseif ($request->filled('logo_dark_url')) {
            SystemSetting::set('system_logo_dark', $validated['logo_dark_url']);
            SystemSetting::set('system_logo', $validated['logo_dark_url']);
        } elseif ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowedImageExts, true)) {
                return back()->withErrors(['logo_file' => 'Logo desteklenen formatta olmalıdır: PNG, JPG, JPEG, SVG, WebP.']);
            }
            $filename = 'logo_' . time() . '_' . Str::random(6) . '.' . $ext;
            $file->move($destinationPath, $filename);
            $logoPath = '/uploads/branding/' . $filename;
            SystemSetting::set('system_logo_dark', $logoPath);
            SystemSetting::set('system_logo', $logoPath);
        } elseif ($request->filled('logo_url')) {
            SystemSetting::set('system_logo_dark', $validated['logo_url']);
            SystemSetting::set('system_logo', $validated['logo_url']);
        }

        // Light theme logo handling
        if ($request->hasFile('logo_light_file')) {
            $file = $request->file('logo_light_file');
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowedImageExts, true)) {
                return back()->withErrors(['logo_light_file' => 'Aydınlık tema logosu desteklenen formatta olmalıdır: PNG, JPG, JPEG, SVG, WebP.']);
            }
            $filename = 'logo_light_' . time() . '_' . Str::random(6) . '.' . $ext;
            $file->move($destinationPath, $filename);
            $logoLightPath = '/uploads/branding/' . $filename;
            SystemSetting::set('system_logo_light', $logoLightPath);
        } elseif ($request->filled('logo_light_url')) {
            SystemSetting::set('system_logo_light', $validated['logo_light_url']);
        }

        // Favicon handling
        if ($request->hasFile('favicon_file')) {
            $file = $request->file('favicon_file');
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowedFaviconExts, true)) {
                return back()->withErrors(['favicon_file' => 'Favicon desteklenen formatta olmalıdır: ICO, PNG, SVG, WebP.']);
            }
            $filename = 'favicon_' . time() . '_' . Str::random(6) . '.' . $ext;
            $file->move($destinationPath, $filename);
            $faviconPath = '/uploads/branding/' . $filename;
            SystemSetting::set('system_favicon', $faviconPath);
        } elseif ($request->filled('favicon_url')) {
            SystemSetting::set('system_favicon', $validated['favicon_url']);
        }

        if ($request->filled('brand_name')) {
            SystemSetting::set('brand_name', $validated['brand_name']);
        }

        AuditLog::log('system_settings.logo_updated', 'SystemSetting', 0, [
            'logo_dark' => SystemSetting::get('system_logo_dark') ?: SystemSetting::get('system_logo'),
            'logo_light' => SystemSetting::get('system_logo_light'),
            'favicon' => SystemSetting::get('system_favicon'),
            'brand_name' => SystemSetting::get('brand_name'),
        ]);

        return back()->with('success', 'Sistem logoları, favicon ve marka ayarları başarıyla güncellendi.');
    }

    /**
     * Update landing and features page content and list of features.
     */
    public function updateFeatures(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'features_badge' => ['nullable', 'string', 'max:150'],
            'features_title' => ['nullable', 'string', 'max:255'],
            'features_subtitle' => ['nullable', 'string', 'max:1000'],
            'features_list' => ['nullable', 'array'],
            'features_list.*.id' => ['nullable', 'string'],
            'features_list.*.title' => ['required', 'string', 'max:200'],
            'features_list.*.description' => ['required', 'string', 'max:1000'],
            'features_list.*.icon' => ['nullable', 'string', 'max:50'],
            'features_list.*.color' => ['nullable', 'string', 'max:50'],
            'features_list.*.badge' => ['nullable', 'string', 'max:80'],
            'features_list.*.is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['features_badge'])) {
            SystemSetting::set('features_page_badge', trim($validated['features_badge']));
        }
        if (isset($validated['features_title'])) {
            SystemSetting::set('features_page_title', trim($validated['features_title']));
        }
        if (isset($validated['features_subtitle'])) {
            SystemSetting::set('features_page_subtitle', trim($validated['features_subtitle']));
        }
        if (isset($validated['features_list'])) {
            $cleaned = array_map(function ($item) {
                return [
                    'id' => !empty($item['id']) ? $item['id'] : Str::random(8),
                    'title' => trim($item['title']),
                    'description' => trim($item['description']),
                    'icon' => !empty($item['icon']) ? trim($item['icon']) : 'Activity',
                    'color' => !empty($item['color']) ? trim($item['color']) : 'indigo',
                    'badge' => !empty($item['badge']) ? trim($item['badge']) : null,
                    'is_active' => isset($item['is_active']) ? (bool)$item['is_active'] : true,
                ];
            }, $validated['features_list']);

            SystemSetting::set('features_page_list', array_values($cleaned));
        }

        AuditLog::log('system_settings.features_updated', 'SystemSetting', 0, [
            'features_count' => count($validated['features_list'] ?? []),
        ]);

        return back()->with('success', 'Özellikler sayfası ve içerikleri başarıyla kaydedildi.');
    }

    /**
     * Reset features to system defaults.
     */
    public function resetFeatures(Request $request)
    {
        $this->authorizeAdmin($request);

        SystemSetting::set('features_page_badge', 'Platform Özellikleri & Mimarisi');
        SystemSetting::set('features_page_title', 'Teknik SEO & Analiz Altyapısı');
        SystemSetting::set('features_page_subtitle', 'Kendi sunucunuzda çalışan, çoklu çalışma alanları, SSRF korumalı crawler ve 25+ teknik analiz kuralı içeren kurumsal platform.');
        SystemSetting::set('features_page_list', SystemSetting::getDefaultFeatures());

        AuditLog::log('system_settings.features_reset', 'SystemSetting', 0);

        return back()->with('success', 'Özellikler sayfası varsayılan ayarlara sıfırlandı.');
    }

    protected function authorizeAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->is_platform_admin) {
            abort(403, 'Bu sayfaya erişim için platform yöneticisi yetkisi gereklidir.');
        }
    }
}
