<?php

namespace App\Modules\Install;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InstallController extends Controller
{
    public function index(Request $request)
    {
        $keyFile = storage_path('install_key.txt');
        if (!file_exists($keyFile)) {
            $initialKey = Str::random(32);
            file_put_contents($keyFile, $initialKey);
        }

        $systemChecks = $this->checkSystemRequirements();

        return Inertia::render('Install/Index', [
            'requirements' => $systemChecks,
            'keyFileExists' => true,
            'serverKeyPath' => $keyFile,
        ]);
    }

    public function process(Request $request)
    {
        $keyFile = storage_path('install_key.txt');
        $expectedKey = file_exists($keyFile) ? trim(file_get_contents($keyFile)) : null;

        $validated = $request->validate([
            'setup_key' => ['required', 'string'],
            'app_url' => ['required', 'url'],
            'install_mode' => ['required', 'in:self_hosted,saas'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (empty($expectedKey) || !hash_equals($expectedKey, $validated['setup_key'])) {
            return back()->withErrors([
                'setup_key' => 'Sunucu kurulum güvenlik anahtarı hatalı. Lütfen storage/install_key.txt dosyasındaki anahtarı girin.',
            ]);
        }

        try {
            // 1. Run migrations safely
            Artisan::call('migrate', ['--force' => true]);

            // 2. Create Platform Admin User
            $admin = User::firstOrCreate(
                ['email' => $validated['admin_email']],
                [
                    'name' => $validated['admin_name'],
                    'password' => Hash::make($validated['admin_password']),
                    'is_platform_admin' => true,
                    'email_verified_at' => now(),
                ]
            );

            // 3. Create default primary workspace
            $workspace = Workspace::firstOrCreate(
                ['owner_id' => $admin->id],
                [
                    'name' => 'Birincil Çalışma Alanı',
                    'slug' => 'primary-workspace-' . Str::random(4),
                    'personal_team' => true,
                    'is_active' => true,
                ]
            );

            if (!$workspace->users()->where('users.id', $admin->id)->exists()) {
                $workspace->users()->attach($admin->id, ['role' => 'owner']);
            }

            Subscription::firstOrCreate(
                ['workspace_id' => $workspace->id],
                [
                    'plan_name' => $validated['install_mode'] === 'self_hosted' ? 'agency' : 'free',
                    'status' => 'active',
                    'limits' => [
                        'max_projects' => $validated['install_mode'] === 'self_hosted' ? 9999 : 1,
                        'max_pages_monthly' => $validated['install_mode'] === 'self_hosted' ? 999999 : 500,
                        'max_keywords' => $validated['install_mode'] === 'self_hosted' ? 9999 : 10,
                        'team_members' => $validated['install_mode'] === 'self_hosted' ? 999 : 1,
                    ],
                ]
            );

            $admin->current_workspace_id = $workspace->id;
            $admin->save();

            // 4. Update .env with install mode and app_url
            $this->updateEnv([
                'APP_URL' => $validated['app_url'],
                'APP_INSTALL_MODE' => $validated['install_mode'],
            ]);

            // 5. Write permanent lock file
            $lockData = json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '1.0.0',
                'mode' => $validated['install_mode'],
                'hash' => hash('sha256', $validated['app_url'] . now()->toIso8601String()),
            ], JSON_PRETTY_PRINT);

            file_put_contents(storage_path('installed.lock'), $lockData);

            // Clean up temporary setup key file
            if (file_exists($keyFile)) {
                @unlink($keyFile);
            }

            AuditLog::log('install.completed', 'User', $admin->id, [
                'mode' => $validated['install_mode'],
            ]);

            return redirect('/login')->with('success', 'Kurulum başarıyla tamamlandı! Oluşturduğunuz yönetici hesabıyla giriş yapabilirsiniz.');

        } catch (\Throwable $e) {
            return back()->withErrors([
                'error' => 'Kurulum sırasında bir hata oluştu: ' . $e->getMessage(),
            ]);
        }
    }

    protected function checkSystemRequirements(): array
    {
        return [
            'php_version' => [
                'name' => 'PHP Sürümü (>= 8.2)',
                'pass' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
            ],
            'pdo' => [
                'name' => 'PDO Uzantısı',
                'pass' => extension_loaded('pdo'),
            ],
            'mbstring' => [
                'name' => 'Mbstring Uzantısı',
                'pass' => extension_loaded('mbstring'),
            ],
            'openssl' => [
                'name' => 'OpenSSL Uzantısı',
                'pass' => extension_loaded('openssl'),
            ],
            'curl' => [
                'name' => 'cURL Uzantısı',
                'pass' => extension_loaded('curl'),
            ],
            'storage_writable' => [
                'name' => 'storage Dizin İzni',
                'pass' => is_writable(storage_path()),
            ],
            'bootstrap_cache_writable' => [
                'name' => 'bootstrap/cache Dizin İzni',
                'pass' => is_writable(base_path('bootstrap/cache')),
            ],
        ];
    }

    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);
        foreach ($values as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
