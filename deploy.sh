#!/bin/bash
set -e

echo "=== Seovy Production Deployment Starting ==="

# 1. Bakım modunu aç
php artisan down || true

# 2. En son kodları çek
git pull origin master

# 3. PHP Bağımlılıklarını optimize et
composer install --no-dev --optimize-autoloader

# 4. Veritabanı migration'larını uygula (otomatik onaylı)
php artisan migrate --force

# 5. Önbellekleri temizle ve optimize et
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Ön yüz varlıklarını derle (sunucuda node varsa)
if command -v npm &> /dev/null
then
    npm ci
    npm run build
fi

# 7. Queue Worker ve Scheduler'ı yeniden başlat
php artisan queue:restart

# 8. Bakım modunu kapat
php artisan up

echo "=== Seovy Production Deployment Completed Successfully! ==="
