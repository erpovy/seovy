<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppInstallCommand extends Command
{
    protected $signature = 'app:install {--cli : Run in non-interactive CLI mode with environment variables}';
    protected $description = 'Seovy platformunu kurar, migrationları ve yönetici hesabını oluşturur';

    public function handle(): int
    {
        $lockFile = storage_path('installed.lock');

        if (file_exists($lockFile)) {
            $this->error('Uygulama daha önce kurulmuş ve kilitlenmiştir! Yeniden kurmak için `php artisan app:unlock` komutunu kullanın.');
            return 1;
        }

        $this->info('=========================================');
        $this->info('   Seovy Platform Kurulum Sihirbazı     ');
        $this->info('=========================================');

        // Check key
        if (!config('app.key')) {
            $this->info('APP_KEY üretiliyor...');
            Artisan::call('key:generate', ['--force' => true]);
        }

        $this->info('Veritabanı tabloları hazırlanıyor...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());

        $adminEmail = $this->option('cli') ? env('ADMIN_EMAIL', 'admin@seovy.local') : $this->ask('Yönetici E-posta Adresi', 'admin@seovy.local');
        $adminName = $this->option('cli') ? env('ADMIN_NAME', 'Sistem Yöneticisi') : $this->ask('Yönetici Adı', 'Sistem Yöneticisi');
        $adminPass = $this->option('cli') ? env('ADMIN_PASSWORD', 'SeovyAdmin2026!') : $this->secret('Yönetici Parolası (en az 8 karakter)');

        if (!$this->option('cli') && strlen($adminPass) < 8) {
            $this->error('Parola en az 8 karakter olmalıdır.');
            return 1;
        }

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPass),
                'is_platform_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $workspace = Workspace::firstOrCreate(
            ['owner_id' => $admin->id],
            [
                'name' => 'Varsayılan Çalışma Alanı',
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
                'plan_name' => 'agency',
                'status' => 'active',
                'limits' => [
                    'max_projects' => 9999,
                    'max_pages_monthly' => 999999,
                    'max_keywords' => 9999,
                    'team_members' => 999,
                ],
            ]
        );

        $admin->current_workspace_id = $workspace->id;
        $admin->save();

        // Create lock file
        $lockData = json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => '1.0.0',
            'cli' => true,
        ], JSON_PRETTY_PRINT);

        file_put_contents($lockFile, $lockData);

        // Delete temporary install key if exists
        $keyFile = storage_path('install_key.txt');
        if (file_exists($keyFile)) {
            @unlink($keyFile);
        }

        $this->info('Tebrikler! Seovy başarıyla kuruldu ve kilitlendi.');
        $this->info("Giriş e-postası: {$adminEmail}");

        return 0;
    }
}
