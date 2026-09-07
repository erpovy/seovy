<?php

use App\Http\Middleware\EnsureNotInstalled;
use App\Modules\Admin\AdminController;
use App\Modules\AiSeo\AiSeoController;
use App\Modules\Auth\AuthController;
use App\Modules\Auth\ProfileController;
use App\Modules\Auth\TwoFactorController;
use App\Modules\Billing\SubscriptionController;
use App\Modules\ContentWorkspace\OnPageController;
use App\Modules\ContentWorkspace\TaskController;
use App\Modules\Crawler\CrawlController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Install\InstallController;
use App\Modules\Integrations\IntegrationController;
use App\Modules\Integrations\KeywordController;
use App\Modules\Project\ProjectController;
use App\Modules\Reports\ReportController;
use App\Modules\Workspace\WorkspaceController;
use App\Modules\Workspace\WorkspaceMemberController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 1. Root & Public Routes
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'featuresBadge' => \App\Models\SystemSetting::get('features_page_badge', 'Platform Özellikleri & Mimarisi'),
        'featuresTitle' => \App\Models\SystemSetting::get('features_page_title', 'Teknik SEO & Analiz Altyapısı'),
        'featuresSubtitle' => \App\Models\SystemSetting::get('features_page_subtitle', 'Kendi sunucunuzda çalışan, çoklu çalışma alanları, SSRF korumalı crawler ve 25+ teknik analiz kuralı içeren kurumsal platform.'),
        'featuresList' => \App\Models\SystemSetting::get('features_page_list', \App\Models\SystemSetting::getDefaultFeatures()),
    ]);
})->name('home');

// Dedicated Standalone Features Page
Route::get('/features', function () {
    return Inertia::render('Features', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'featuresBadge' => \App\Models\SystemSetting::get('features_page_badge', 'Platform Özellikleri & Mimarisi'),
        'featuresTitle' => \App\Models\SystemSetting::get('features_page_title', 'Teknik SEO & Analiz Altyapısı'),
        'featuresSubtitle' => \App\Models\SystemSetting::get('features_page_subtitle', 'Kendi sunucunuzda çalışan, çoklu çalışma alanları, SSRF korumalı crawler ve 25+ teknik analiz kuralı içeren kurumsal platform.'),
        'featuresList' => \App\Models\SystemSetting::get('features_page_list', \App\Models\SystemSetting::getDefaultFeatures()),
    ]);
})->name('features');

// Shared Public Reports
Route::get('/reports/shared/{token}', [ReportController::class, 'publicView'])->name('reports.shared');

// Workspace Invitations
Route::get('/invitations/{token}/accept', [WorkspaceMemberController::class, 'acceptInvitation'])->name('invitations.accept');

// 2. Install Wizard Routes (Guarded against re-installation)
Route::middleware([EnsureNotInstalled::class])->group(function () {
    Route::get('/install', [InstallController::class, 'index'])->name('install.index');
    Route::post('/install/test-db', [InstallController::class, 'testDatabase'])->name('install.test-db');
    Route::post('/install', [InstallController::class, 'process'])->name('install.process');
});

// 3. Guest Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// 4. Authenticated Application Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile & Sessions
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/logout-other-sessions', [ProfileController::class, 'logoutOtherSessions'])->name('profile.logout-other');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 2FA
    Route::post('/profile/two-factor', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::delete('/profile/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // Workspaces
    Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::patch('/workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');
    Route::get('/workspaces/{workspace}/export', [WorkspaceController::class, 'exportData'])->name('workspaces.export');

    // Workspace Members & Roles
    Route::get('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index'])->name('workspaces.members');
    Route::post('/workspaces/{workspace}/members/invite', [WorkspaceMemberController::class, 'invite'])->name('workspaces.members.invite');
    Route::patch('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'updateRole'])->name('workspaces.members.role');
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'removeMember'])->name('workspaces.members.remove');

    // Projects
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/verify', [ProjectController::class, 'verifyOwnership'])->name('projects.verify');
    Route::get('/projects/{project}/wordpress-plugin', [ProjectController::class, 'downloadWordpressPlugin'])->name('projects.wp-plugin');

    // Crawls & Crawler
    Route::post('/projects/{project}/crawls/start', [CrawlController::class, 'start'])->name('crawls.start');
    Route::get('/projects/{project}/crawls/{crawl}', [CrawlController::class, 'show'])->name('crawls.show');
    Route::get('/projects/{project}/crawls/{crawl}/status', [CrawlController::class, 'status'])->name('crawls.status');
    Route::post('/projects/{project}/crawls/{crawl}/pause', [CrawlController::class, 'pause'])->name('crawls.pause');
    Route::post('/projects/{project}/crawls/{crawl}/cancel', [CrawlController::class, 'cancel'])->name('crawls.cancel');
    Route::get('/projects/{project}/crawls/{crawl1}/compare/{crawl2}', [CrawlController::class, 'compare'])->name('crawls.compare');

    // On-Page SEO Analysis
    Route::get('/projects/{project}/on-page', [OnPageController::class, 'show'])->name('on-page.show');
    Route::post('/projects/{project}/on-page/live', [OnPageController::class, 'analyzeLive'])->name('on-page.live');

    // AI Search & GEO (Generative Engine Optimization)
    Route::get('/projects/{project}/ai-seo', [AiSeoController::class, 'show'])->name('ai-seo.show');

    // SEO Tasks
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/projects/{project}/findings/{finding}/convert-task', [TaskController::class, 'convertFromFinding'])->name('tasks.convert');

    // Reports & Exports
    Route::get('/projects/{project}/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/projects/{project}/crawls/{crawl}/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');
    Route::post('/projects/{project}/crawls/{crawl}/generate-pdf', [ReportController::class, 'generatePdf'])->name('reports.generate-pdf');
    Route::get('/projects/{project}/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');

    // Keywords & Rank Tracker
    Route::get('/projects/{project}/keywords', [KeywordController::class, 'index'])->name('keywords.index');
    Route::post('/projects/{project}/keywords', [KeywordController::class, 'store'])->name('keywords.store');
    Route::post('/projects/{project}/keywords/check', [KeywordController::class, 'checkRankings'])->name('keywords.check');
    Route::post('/projects/{project}/keywords/serp-settings', [KeywordController::class, 'saveSerpSettings'])->name('keywords.serp-settings');
    Route::post('/projects/{project}/keywords/import-csv', [KeywordController::class, 'importCsv'])->name('keywords.import-csv');
    Route::delete('/projects/{project}/keywords/{keyword}', [KeywordController::class, 'destroy'])->name('keywords.destroy');

    // Integrations (GSC, GA4, PageSpeed)
    Route::get('/projects/{project}/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('/projects/{project}/integrations/save', [IntegrationController::class, 'saveCredentials'])->name('integrations.save');
    Route::post('/projects/{project}/integrations/pagespeed', [IntegrationController::class, 'runPageSpeed'])->name('integrations.pagespeed');
    Route::delete('/projects/{project}/integrations/{type}', [IntegrationController::class, 'disconnect'])->name('integrations.disconnect');

    // Billing & Plans (SaaS)
    Route::get('/billing', [SubscriptionController::class, 'index'])->name('billing.index');
    Route::post('/billing/plan', [SubscriptionController::class, 'updatePlan'])->name('billing.plan');
    Route::post('/billing/checkout', [SubscriptionController::class, 'checkout'])->name('billing.checkout');

    // Platform Admin Panel
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/workspaces', [AdminController::class, 'workspaces'])->name('workspaces');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
        Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
        Route::patch('/payments/gateways/{gateway}', [AdminController::class, 'updateGateway'])->name('payments.gateways.update');
        Route::post('/payments/settings', [AdminController::class, 'updatePaymentSettings'])->name('payments.settings');
        Route::post('/payments/simulate', [AdminController::class, 'simulatePayment'])->name('payments.simulate');
        Route::post('/payments/transactions/{transaction}/refund', [AdminController::class, 'simulateRefund'])->name('payments.refund');

        // Subscription Plans & Quota Management
        Route::get('/plans', [AdminController::class, 'plans'])->name('plans');
        Route::post('/plans', [AdminController::class, 'storePlan'])->name('plans.store');
        Route::patch('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');
        Route::delete('/plans/{plan}', [AdminController::class, 'destroyPlan'])->name('plans.destroy');

        // Sales & Purchase Logs
        Route::get('/sales', [AdminController::class, 'sales'])->name('sales');

        // System Logo & Branding Settings
        Route::post('/settings/logo', [AdminController::class, 'updateLogo'])->name('settings.logo');

        // Platform Features Page & Customization
        Route::post('/settings/features', [AdminController::class, 'updateFeatures'])->name('settings.features');
        Route::post('/settings/features/reset', [AdminController::class, 'resetFeatures'])->name('settings.features.reset');
    });
});

// Stripe Webhook (CSRF exempt handled in bootstrap/app.php)
Route::post('/webhooks/stripe', [SubscriptionController::class, 'handleWebhook'])->name('webhooks.stripe');

// Multi-Language Locale Switcher
Route::post('/locale/{locale}', [\App\Http\Controllers\LocaleController::class, 'update'])->name('locale.update');

// Static Asset Fallback (ensures Vite chunks and manifest load even if web server rewrites to index.php)
Route::get('/build/assets/{file}', function ($file) {
    $path = public_path('build/assets/' . $file);
    if (!file_exists($path)) {
        if (str_ends_with($file, '.js')) {
            $files = glob(public_path('build/assets/*.js'));
            if (!empty($files)) {
                usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
                $path = $files[0];
            }
        } elseif (str_ends_with($file, '.css')) {
            $files = glob(public_path('build/assets/*.css'));
            if (!empty($files)) {
                usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
                $path = $files[0];
            }
        }
    }

    if ($path && file_exists($path)) {
        $mime = str_ends_with($path, '.js') ? 'application/javascript' : (str_ends_with($path, '.css') ? 'text/css' : 'application/octet-stream');
        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    abort(404);
})->where('file', '.*');

Route::get('/build/manifest.json', function () {
    $path = public_path('build/manifest.json');
    if (file_exists($path)) {
        return response()->file($path, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
    abort(404);
});

// Uploaded Branding and Static File Serving Fallback
Route::get('/uploads/{path}', function ($path) {
    $fullPath = public_path('uploads/' . $path);
    if (file_exists($fullPath) && is_file($fullPath)) {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimes = [
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
        ];
        $mime = $mimes[$ext] ?? (mime_content_type($fullPath) ?: 'application/octet-stream');
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
    abort(404);
})->where('path', '.*');

