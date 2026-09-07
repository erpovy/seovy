<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

class AppPackageCommand extends Command
{
    protected $signature = 'app:package {--ver=1.0.0 : Dağıtım sürüm numarası}';
    protected $description = 'CodeCanyon ve yazılım pazaryerleri için temiz, kullanıma hazır kurulum ZIP paketi üretir';

    public function handle(): int
    {
        $version = $this->option('ver') ?: '1.0.0';
        $distDir = base_path('dist');
        $zipFile = $distDir . DIRECTORY_SEPARATOR . "seovy-v{$version}-codecanyon.zip";
        $stagingDir = $distDir . DIRECTORY_SEPARATOR . "staging";

        $this->info("=================================================");
        $this->info("   Seovy CodeCanyon Dağıtım Paketi Oluşturucu    ");
        $this->info("   Sürüm: v{$version}                            ");
        $this->info("=================================================");

        // 1. Verify build assets exist
        $buildManifest = public_path('build/manifest.json');
        if (!file_exists($buildManifest)) {
            $this->error('Hata: public/build/manifest.json bulunamadı! Lütfen önce `npm run build` çalıştırın.');
            return 1;
        }

        // 2. Ensure dist and clean staging directory exists
        if (!is_dir($distDir)) {
            mkdir($distDir, 0755, true);
        }

        if (is_dir($stagingDir)) {
            $this->deleteDirectory($stagingDir);
        }
        mkdir($stagingDir, 0755, true);

        // 3. Write README-INSTALLATION.txt
        $readmeContent = <<<TXT
================================================================================
   SEOVY - KURULUM VE BAŞLANGIÇ KILAVUZU (QUICK INSTALLATION GUIDE)
================================================================================
Sürüm: v{$version}
Gereksinimler: PHP >= 8.2 (pdo, mbstring, openssl, curl, fileinfo, xml, gd)

--------------------------------------------------------------------------------
1. CPANEL / PAYLAŞIMLI HOSTİNG KURULUMU (SHARED HOSTING):
--------------------------------------------------------------------------------
1. İndirdiğiniz ZIP dosyasını cPanel Dosya Yöneticisi (File Manager) ile sitenizin
   kök dizinine (genellikle public_html) yükleyin ve "Extract" ile çıkartın.
2. cPanel -> MySQL Veritabanları bölümünden boş bir veritabanı ve kullanıcı oluşturun,
   kullanıcıya tüm yetkileri verin.
3. Tarayıcınızdan alan adınızı açın (Örn: https://alanadiniz.com).
4. Otomatik olarak açılan Seovy Kurulum Sihirbazı'nda:
   - Sunucu izinlerinin yeşil olduğunu teyit edin.
   - Veritabanı bilgilerinizi girip "Bağlantıyı Test Et" butonuna basın.
   - Yönetici e-posta ve şifrenizi belirleyin.
   - "Kurulumu Tamamla ve Kilitle" butonuna tıklayın.
5. Kurulum 10 saniye içinde tamamlanacaktır!

--------------------------------------------------------------------------------
2. CRON JOB AYARI (GÜNLÜK SERP VE TARAMA GÖREVLERİ İÇİN):
--------------------------------------------------------------------------------
cPanel -> Cron Jobs (Zamanlanmış Görevler) bölümüne gidin ve her dakikada bir (* * * * *)
çalışacak şekilde şu komutu ekleyin:

* * * * * cd /home/kullanici_adiniz/public_html && php artisan schedule:run >> /dev/null 2>&1

--------------------------------------------------------------------------------
3. DESTEK VE YARDIM:
--------------------------------------------------------------------------------
Herhangi bir sorunuzda Envato / CodeCanyon profilimiz üzerinden destek talebi
açabilirsiniz.
================================================================================
TXT;
        file_put_contents($distDir . DIRECTORY_SEPARATOR . 'README-INSTALLATION.txt', $readmeContent);
        file_put_contents($stagingDir . DIRECTORY_SEPARATOR . 'README-INSTALLATION.txt', $readmeContent);

        $basePath = realpath(base_path());
        $this->info("Dosyalar taranıyor ve staging alanına kopyalanıyor...");

        // Directories / files to strictly exclude from commercial zip
        $excludePatterns = [
            '/\.git/',
            '/\.github/',
            '/node_modules/',
            '/tests/',
            '/scratch/',
            '/dist/',
            '/\.env$/',
            '/storage\/installed\.lock$/',
            '/storage\/install_key\.txt$/',
            '/storage\/logs\/.*\.log$/',
            '/storage\/framework\/cache\/data\/.+/',
            '/storage\/framework\/sessions\/.+/',
            '/storage\/framework\/views\/.+/',
        ];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $fileCount = 0;
        foreach ($files as $name => $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($basePath) + 1);
            $normalizedPath = str_replace('\\', '/', $relativePath);

            // Check exclusion
            $shouldExclude = false;
            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, '/' . $normalizedPath)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $targetPath = $stagingDir . DIRECTORY_SEPARATOR . $relativePath;
            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            copy($filePath, $targetPath);
            $fileCount++;
        }

        // Add empty directory placeholders in staging
        @mkdir($stagingDir . '/storage/framework/cache/data', 0755, true);
        @mkdir($stagingDir . '/storage/framework/sessions', 0755, true);
        @mkdir($stagingDir . '/storage/framework/views', 0755, true);
        @mkdir($stagingDir . '/storage/logs', 0755, true);

        // 4. Create ZIP package
        if (file_exists($zipFile)) {
            @unlink($zipFile);
        }

        $this->info("ZIP arşivi oluşturuluyor ({$fileCount} dosya)...");

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $stagingFiles = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($stagingDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($stagingFiles as $name => $file) {
                    if (!$file->isFile()) continue;
                    $rel = substr($file->getRealPath(), strlen($stagingDir) + 1);
                    $zip->addFile($file->getRealPath(), str_replace('\\', '/', $rel));
                }
                $zip->close();
            }
        } else {
            // Fallback to native Windows/Linux tar command
            $escapedZip = escapeshellarg($zipFile);
            $cmd = "tar -a -c -f {$escapedZip} -C " . escapeshellarg($stagingDir) . " .";
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($zipFile)) {
                // Fallback to powershell Compress-Archive
                $psCmd = "powershell -Command \"Compress-Archive -Path '{$stagingDir}\\*' -DestinationPath '{$zipFile}' -Force\"";
                exec($psCmd, $output, $returnCode);
            }
        }

        // Clean up staging directory
        $this->deleteDirectory($stagingDir);

        if (!file_exists($zipFile)) {
            $this->error("Hata: ZIP paketi üretilemedi.");
            return 1;
        }

        $zipSizeMb = round(filesize($zipFile) / (1024 * 1024), 2);
        $checksum = hash_file('sha256', $zipFile);

        $this->info("=================================================");
        $this->info("   Tebrikler! Dağıtım Paketi Hazırlandı!        ");
        $this->info("=================================================");
        $this->line("Dosya Konumu: <comment>{$zipFile}</comment>");
        $this->line("Toplam Dosya Sayısı: <comment>{$fileCount}</comment>");
        $this->line("Paket Boyutu: <comment>{$zipSizeMb} MB</comment>");
        $this->line("SHA-256: <comment>{$checksum}</comment>");

        return 0;
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($dir);
    }
}
