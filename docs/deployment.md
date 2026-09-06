# Seovy — Dağıtım ve İşletim Kılavuzu (Deployment & Operations)

Bu belge, Seovy platformunun Linux sunucularında (Ubuntu 22.04 / 24.04 LTS veya Debian 12) Nginx, PHP-FPM 8.2+, PostgreSQL 16 ve Redis ile sıfırdan kurulumunu ve Docker Compose ile ayağa kaldırılmasını anlatır.

---

## 1. Sistem Gereksinimleri

- **İşletim Sistemi**: Linux (Ubuntu 22.04 / 24.04 LTS veya Debian 12 önerilir).
- **İşlemci & RAM**: Minimum 2 vCPU, 4 GB RAM (Önerilen: 4 vCPU, 8 GB RAM).
- **PHP**: PHP 8.2 veya 8.3 (Gerekli modüller: `bcmath`, `curl`, `dom`, `fileinfo`, `intl`, `mbstring`, `openssl`, `pdo`, `pdo_pgsql`, `redis`, `xml`, `zip`).
- **Veritabanı**: PostgreSQL 16+ (Kullanıcı ve UTF-8 veritabanı).
- **Önbellek & Kuyruk**: Redis 7.x.
- **Web Sunucusu**: Nginx 1.24+ (HTTPS / SSL sertifikası zorunludur).
- **Node.js**: Node 20+ ve NPM (Frontend asset derleme için).

---

## 2. Docker Compose ile Dağıtım (Hızlı Kurulum)

1. Projeyi sunucuya klonlayın:
   ```bash
   git clone <repo_url> /var/www/seovy
   cd /var/www/seovy
   ```

2. Ortam dosyasını kopyalayın ve düzenleyin:
   ```bash
   cp .env.example .env
   ```

3. Docker servislerini başlatın:
   ```bash
   docker compose up -d --build
   ```

4. Konteyner içinde kurulum ve anahtar komutlarını çalıştırın:
   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan app:install --cli
   ```

---

## 3. Bağımsız / Manuel Kurulum (Docker Kullanmadan)

### A. Dizin İzinleri
Laravel'in yazma iznine ihtiyaç duyduğu dizinler:
```bash
sudo chown -R www-data:www-data /var/www/seovy/storage /var/www/seovy/bootstrap/cache
sudo chmod -R 775 /var/www/seovy/storage /var/www/seovy/bootstrap/cache
```

### B. Bağımlılıkların Kurulumu ve Asset Derleme
```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### C. Nginx Yapılandırması
`/etc/nginx/sites-available/seovy` oluşturun:
```nginx
server {
    listen 80;
    server_name seovy.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seovy.example.com;
    root /var/www/seovy/public;

    ssl_certificate /etc/letsencrypt/live/seovy.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seovy.example.com/privkey.pem;

    index index.php index.html;
    charset utf-8;
    client_max_body_size 64M;

    # Güvenlik Başlıkları
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### D. Systemd ile Queue Worker ve Scheduler
Queue worker'ı arka planda güvenilir şekilde çalıştırmak için Supervisor veya Systemd kullanılır.

`/etc/systemd/system/seovy-worker.service`:
```ini
[Unit]
Description=Seovy Queue Worker
After=network.target redis.service postgresql.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/seovy/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

Servisi başlatın:
```bash
sudo systemctl daemon-reload
sudo systemctl enable --now seovy-worker
```

### E. Cron / Scheduler Yapılandırması
`sudo crontab -u www-data -e` komutuyla cron tanımlayın:
```cron
* * * * * cd /var/www/seovy && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Güvenlik ve Ağ Gereksinimleri

1. **SSRF İzolasyonu**: Tarayıcı sunucusunun iç ağa (örnek: 10.0.0.0/8, 192.168.0.0/16, link-local 169.254.169.254) erişimi engellenmelidir. Yazılımsal SSRF korumasına ek olarak sunucu firewall (iptables / ufw) seviyesinde dışarıya yalnızca 80 ve 443 portlarından giden bağlantılara izin verilmesi önerilir.
2. **PostgreSQL ve Redis**: Yalnızca `127.0.0.1` veya Docker iç ağına bağlı olmalı, internete açık port bırakılmamalıdır.
