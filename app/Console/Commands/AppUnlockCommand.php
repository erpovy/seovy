<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppUnlockCommand extends Command
{
    protected $signature = 'app:unlock {--force : Kilit dosyasını doğrudan sil}';
    protected $description = 'Kurulum kilidini kaldırarak /install sihirbazını yeniden erişilebilir yapar';

    public function handle(): int
    {
        $lockFile = storage_path('installed.lock');

        if (!file_exists($lockFile)) {
            $this->warn('Kurulum kilidi zaten mevcut değil.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Kurulum kilidini kaldırmak istediğinize emin misiniz?')) {
            $this->info('İşlem iptal edildi.');
            return 0;
        }

        unlink($lockFile);
        $this->info('Kurulum kilidi başarıyla kaldırıldı. Şimdi /install sayfasına erişebilirsiniz.');

        return 0;
    }
}
