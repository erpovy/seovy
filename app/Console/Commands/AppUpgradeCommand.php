<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppUpgradeCommand extends Command
{
    protected $signature = 'app:upgrade';
    protected $description = 'Seovy platformunu güvenle yükseltir (migration ve önbellek yenileme)';

    public function handle(): int
    {
        $this->info('Seovy sistemi güncelleniyor...');

        $this->info('1/3 Veritabanı tabloları güncelleniyor...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());

        $this->info('2/3 Önbellekler temizleniyor...');
        Artisan::call('optimize:clear');

        $this->info('3/3 Önbellekler optimize ediliyor...');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->info('Güncelleme başarıyla tamamlandı!');
        return 0;
    }
}
