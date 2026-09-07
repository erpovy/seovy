<?php

namespace App\Modules\Install;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PDO;

class InstallController extends Controller
{
    /**
     * Display the CodeCanyon Installation Wizard.
     */
    public function index(Request $request): Response
    {
        // 1. Ensure .env exists so Inertia and session do not fail
        $envPath = base_path('.env');
        if (!file_exists($envPath) && file_exists(base_path('.env.example'))) {
            @copy(base_path('.env.example'), $envPath);
        }

        // 2. Perform comprehensive system & permission checks
        $systemChecks = $this->checkSystemRequirements();
        $allPassed = collect($systemChecks)->every(fn($item) => $item['pass']);

        // 3. Read current DB values from environment if available
        $defaultDb = [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'seovy'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ];

        return Inertia::render('Install/Index', [
            'requirements' => $systemChecks,
            'allPassed' => $allPassed,
            'serverInfo' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown Web Server',
                'os' => PHP_OS_FAMILY,
            ],
            'detectedUrl' => $request->root(),
            'defaultDb' => $defaultDb,
        ]);
    }

    /**
     * Test database connectivity in real-time before applying changes.
     */
    public function testDatabase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'db_connection' => ['required', 'string', 'in:mysql,pgsql,sqlite'],
            'db_host' => ['nullable', 'string'],
            'db_port' => ['nullable', 'numeric'],
            'db_database' => ['required', 'string'],
            'db_username' => ['nullable', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        $driver = $validated['db_connection'];
        $database = $validated['db_database'];
        $host = !empty($validated['db_host']) ? $validated['db_host'] : '127.0.0.1';
        $port = $validated['db_port'] ?? null;
        $username = $validated['db_username'] ?? '';
        $password = $validated['db_password'] ?? '';

        try {
            if ($driver === 'sqlite') {
                if ($database !== ':memory:' && !file_exists($database)) {
                    $dir = dirname($database);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    @touch($database);
                }
                $pdo = new PDO("sqlite:{$database}", null, null, [
                    PDO::ATTR_TIMEOUT => 3,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } elseif ($driver === 'pgsql') {
                $port = $port ?: 5432;
                $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_TIMEOUT => 4,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } else { // mysql
                $port = $port ?: 3306;
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_TIMEOUT => 4,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            }

            $pdo->query('SELECT 1');

            return response()->json([
                'success' => true,
                'message' => 'Veritabanı bağlantısı başarıyla doğrulandı! (Connection Successful)',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process full installation: update .env, migrate DB, create admin & lock setup.
     */
    public function process(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_code' => ['nullable', 'string', 'max:100'],
            'db_connection' => ['required', 'string', 'in:mysql,pgsql,sqlite'],
            'db_host' => ['required_unless:db_connection,sqlite', 'nullable', 'string'],
            'db_port' => ['nullable', 'numeric'],
            'db_database' => ['required', 'string'],
            'db_username' => ['nullable', 'string'],
            'db_password' => ['nullable', 'string'],
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url'],
            'install_mode' => ['required', 'in:self_hosted,saas'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // 1. Verify database connection before writing any configuration
        $driver = $validated['db_connection'];
        $database = $validated['db_database'];
        $host = $validated['db_host'] ?: '127.0.0.1';
        $port = $validated['db_port'] ?: ($driver === 'pgsql' ? 5432 : 3306);
        $username = $validated['db_username'] ?? '';
        $password = $validated['db_password'] ?? '';

        try {
            if ($driver === 'sqlite') {
                if ($database !== ':memory:' && !file_exists($database)) {
                    $dir = dirname($database);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    @touch($database);
                }
                $pdo = new PDO("sqlite:{$database}", null, null, [
                    PDO::ATTR_TIMEOUT => 4,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } elseif ($driver === 'pgsql') {
                $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_TIMEOUT => 4,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_TIMEOUT => 4,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            }
            $pdo->query('SELECT 1');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'db_database' => 'Veritabanı bağlantısı kurulamadı: ' . $e->getMessage(),
            ])->withInput();
        }

        try {
            // 2. Update .env file with safe shared hosting defaults
            $this->updateEnv([
                'APP_NAME' => '"' . addslashes($validated['app_name']) . '"',
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => $validated['app_url'],
                'APP_INSTALL_MODE' => $validated['install_mode'],
                'DB_CONNECTION' => $driver,
                'DB_HOST' => $host,
                'DB_PORT' => $port,
                'DB_DATABASE' => $database,
                'DB_USERNAME' => $username,
                'DB_PASSWORD' => $password ? ('"' . addslashes($password) . '"') : '""',
                'SESSION_DRIVER' => 'database',
                'CACHE_STORE' => 'file',
                'QUEUE_CONNECTION' => 'sync',
            ]);

            // 3. Dynamically set runtime database configuration
            config([
                'database.default' => $driver,
                "database.connections.{$driver}.host" => $host,
                "database.connections.{$driver}.port" => $port,
                "database.connections.{$driver}.database" => $database,
                "database.connections.{$driver}.username" => $username,
                "database.connections.{$driver}.password" => $password,
            ]);
            DB::purge();

            // 4. Generate app key if needed
            if (empty(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // 5. Run database migrations safely
            Artisan::call('migrate', ['--force' => true]);

            // 6. Create or update Platform Admin User
            $admin = User::firstOrCreate(
                ['email' => $validated['admin_email']],
                [
                    'name' => $validated['admin_name'],
                    'password' => Hash::make($validated['admin_password']),
                    'is_platform_admin' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Ensure password and admin status are updated if user already existed
            $admin->password = Hash::make($validated['admin_password']);
            $admin->is_platform_admin = true;
            $admin->save();

            // 7. Create default primary workspace
            $workspace = Workspace::firstOrCreate(
                ['owner_id' => $admin->id],
                [
                    'name' => $validated['app_name'] . ' Primary Workspace',
                    'slug' => 'workspace-' . Str::random(5),
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

            // 8. Save initial system settings
            DB::table('system_settings')->updateOrInsert(
                ['key' => 'brand_name'],
                ['value' => $validated['app_name'], 'created_at' => now(), 'updated_at' => now()]
            );

            if (!empty($validated['purchase_code'])) {
                DB::table('system_settings')->updateOrInsert(
                    ['key' => 'purchase_code'],
                    ['value' => $validated['purchase_code'], 'created_at' => now(), 'updated_at' => now()]
                );
            }

            // 9. Storage symlink
            try {
                Artisan::call('storage:link');
            } catch (\Throwable $ignored) {
                // Shared hosting might restrict symlinks; handled gracefully
            }

            // 10. Write permanent lock file
            $lockData = json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '1.0.0',
                'mode' => $validated['install_mode'],
                'app_url' => $validated['app_url'],
                'purchase_code' => !empty($validated['purchase_code']) ? substr($validated['purchase_code'], 0, 8) . '...' : null,
                'hash' => hash('sha256', $validated['app_url'] . now()->toIso8601String()),
            ], JSON_PRETTY_PRINT);

            file_put_contents(storage_path('installed.lock'), $lockData);

            // Clean up temporary setup key if exists
            $keyFile = storage_path('install_key.txt');
            if (file_exists($keyFile)) {
                @unlink($keyFile);
            }

            AuditLog::log('install.completed', 'User', $admin->id, [
                'mode' => $validated['install_mode'],
                'app_url' => $validated['app_url'],
            ]);

            $cronCommand = '* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1';

            return redirect('/login')
                ->with('success', 'Tebrikler! Seovy kurulumu başarıyla tamamlandı. Oluşturduğunuz yönetici hesabıyla giriş yapabilirsiniz.')
                ->with('cron_command', $cronCommand);

        } catch (\Throwable $e) {
            return back()->withErrors([
                'error' => 'Kurulum sırasında bir hata oluştu: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Check PHP version, extensions, and directory write permissions.
     */
    protected function checkSystemRequirements(): array
    {
        $hasPdoDriver = extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql') || extension_loaded('pdo_sqlite');

        return [
            'php_version' => [
                'name' => 'PHP Sürümü (>= 8.2)',
                'pass' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
                'required' => '>= 8.2.0',
            ],
            'pdo' => [
                'name' => 'PDO & Veritabanı Sürücüsü (MySQL/PgSQL/SQLite)',
                'pass' => extension_loaded('pdo') && $hasPdoDriver,
                'required' => 'pdo + (pdo_mysql / pdo_pgsql / pdo_sqlite)',
            ],
            'mbstring' => [
                'name' => 'Mbstring Uzantısı (Çok Dilli Metinler)',
                'pass' => extension_loaded('mbstring'),
                'required' => 'mbstring',
            ],
            'openssl' => [
                'name' => 'OpenSSL Uzantısı (Veri Şifreleme)',
                'pass' => extension_loaded('openssl'),
                'required' => 'openssl',
            ],
            'curl' => [
                'name' => 'cURL Uzantısı (Crawler & API)',
                'pass' => extension_loaded('curl'),
                'required' => 'curl',
            ],
            'fileinfo' => [
                'name' => 'Fileinfo Uzantısı (Dosya & Logo Yükleme)',
                'pass' => extension_loaded('fileinfo'),
                'required' => 'fileinfo',
            ],
            'tokenizer' => [
                'name' => 'Tokenizer Uzantısı (PHP Çekirdek)',
                'pass' => extension_loaded('tokenizer'),
                'required' => 'tokenizer',
            ],
            'xml' => [
                'name' => 'XML Uzantısı (Sitemap & Raporlar)',
                'pass' => extension_loaded('xml'),
                'required' => 'xml',
            ],
            'gd' => [
                'name' => 'GD veya Imagick Uzantısı (Görsel İşleme)',
                'pass' => extension_loaded('gd') || extension_loaded('imagick'),
                'required' => 'gd / imagick',
            ],
            'storage_writable' => [
                'name' => 'storage Dizin İzni (775 / Writable)',
                'pass' => is_writable(storage_path()),
                'required' => 'Writable',
            ],
            'storage_framework_writable' => [
                'name' => 'storage/framework Dizin İzni',
                'pass' => is_writable(storage_path('framework')),
                'required' => 'Writable',
            ],
            'storage_logs_writable' => [
                'name' => 'storage/logs Dizin İzni',
                'pass' => is_writable(storage_path('logs')),
                'required' => 'Writable',
            ],
            'bootstrap_cache_writable' => [
                'name' => 'bootstrap/cache Dizin İzni',
                'pass' => is_writable(base_path('bootstrap/cache')),
                'required' => 'Writable',
            ],
            'env_writable' => [
                'name' => '.env Dosya / Dizin Yazma İzni',
                'pass' => file_exists(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path()),
                'required' => 'Writable',
            ],
        ];
    }

    /**
     * Safely update or append keys in the .env file.
     */
    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                @copy(base_path('.env.example'), $envPath);
            } else {
                @touch($envPath);
            }
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
