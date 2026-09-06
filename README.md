# Seovy - Kurumsal ve Kendi Barındırılabilir SEO Denetim ve Analiz Platformu

Modern, yüksek performanslı, SSRF korumalı ve çok kiracılı (multi-tenant) teknik SEO denetim, anahtar kelime takip ve raporlama platformu.

---

## 🚀 Özellikler ve Mimari Özet

- **Monolitik Laravel 12 + Vue 3 (Inertia.js & TypeScript & Tailwind CSS 4)**
- **Gerçek HTTP Tarayıcı (Crawler)**:
  - SSRF Koruması: Private IPv4 (10/8, 172.16/12, 192.168/16), loopback (127/8, ::1), cloud metadata (169.254.169.254), izinli portlar (80, 443, 8080, 8443) ve DNS rebinding koruması.
  - Robots.txt ve sitemap.xml uyumu.
  - Kuyruk tabanlı asenkron tarama, duraklatma (pause) ve iptal (cancel) desteği.
- **25+ Teknik SEO Analiz Kuralı**:
  - Durum kodları (4xx, 5xx), yönlendirme zincirleri (301/302).
  - Title/meta description eksikliği, piksel ve karakter uzunluk sınırları.
  - H1 eksikliği ve mükerrer H1 tespiti.
  - Canonical etiket hataları (eksik veya göreceli linkler).
  - Robots noindex / nofollow direktifleri.
  - Eksik görsel alt etiketleri, JSON-LD Schema doğrulaması, boş bağlantılar.
  - Şeffaf ve açıklanabilir Sağlık Skoru (Health Score) formülü (0-100).
- **Çok Kiracılı (Multi-Tenant) İzolasyon**:
  - Global query scope (BelongsToWorkspace) ve Laravel Policy katmanı ile mutlak veri izolasyonu.
  - Rol bazlı yetkilendirme (RBAC): Owner, Admin, Specialist, Viewer.
- **Entegrasyonlar**:
  - Google PageSpeed Insights (Lighthouse Lab verileri + CrUX Real-User Field verileri).
  - Google Search Console (GSC) ve GA4 adaptör altyapısı.
- **Raporlama & Görev Yönetimi**:
  - SEO bulgularını tek tıkla aksiyon alınabilir görevlere (SeoTask) dönüştürme.
  - DomPDF ile profesyonel PDF denetim raporu çıktısı.
  - UTF-8 BOM'lu Excel uyumlu CSV dışa aktarımı.
- **Çift Kurulum Modu**:
  - Tek kullanıcılı / kurum içi: Self-Hosted Single-Tenant.
  - Ticari SaaS: Plan ve proje sınırları, Stripe test adaptörü ve webhook işleme.

---

## 🛠️ Sistem Gereksinimleri

- **PHP**: 8.2 veya üzeri
- **PHP Uzantıları**: pdo_pgsql veya pdo_sqlite, curl, mbstring, openssl, xml, zip, gd, cmath
- **Veritabanı**: PostgreSQL 16 (üretim ortamı için tavsiye edilen) veya SQLite (yerel test için)
- **Kuyruk & Önbellek**: Redis 7.x
- **Node.js**: 18+ veya 20+ (ve NPM)
- **Composer**: 2.6+

---

## 📦 Yerel Kurulum (Local Development)

### 1. Depoyu Klonlayın ve Bağımlılıkları Yükleyin
`ash
git clone https://github.com/artovy/seovy.git
cd seovy

composer install
npm install
`

### 2. Ortam Değişkenlerini Ayarlayın
`ash
cp .env.example .env
php artisan key:generate
`

### 3. Veritabanı ve Kurulum
Yerel geliştirme için varsayılan olarak SQLite kullanılabilir (database/database.sqlite):
`ash
# SQLite dosyasını oluşturun (varsa atlayın)
touch database/database.sqlite

# Migration'ları çalıştırın
php artisan migrate

# Demo veri setini yükleyin (Opsiyonel)
php artisan db:seed --class=DemoSeeder
`

### 4. Kurulum Sihirbazı veya CLI Kurulumu
Kurulum sihirbazını başlatmak veya CLI üzerinden tek komutla kurulum yapmak için:
`ash
# CLI ile kurulum (Yönetici hesabı ve ilk çalışma alanı açılır)
php artisan app:install --cli

# veya web arayüzünden kurulum için tarayıcıda /install adresine gidin:
# (Eğer kilitliyse: php artisan app:unlock)
`

### 5. Ön Yüzü Derleyin ve Uygulamayı Başlatın
`ash
# Geliştirme modu (Hot reload):
npm run dev

# Başka bir terminalde Laravel sunucusu:
php artisan serve
`

---

## 🐳 Docker ile Kurulum (Production-ready)

docker-compose.yml dosyası Nginx, PHP 8.2-FPM, PostgreSQL 16, Redis ve otomatik Queue Worker / Scheduler servislerini içerir.

`ash
# 1. Konfigürasyonu hazırlayın
cp .env.example .env

# 2. İmajları derleyin ve ayağa kaldırın
docker compose up -d --build

# 3. Konteyner içinde kurulumu tamamlayın
docker compose exec app php artisan app:install --cli
`

Servisler:
- **Web**: http://localhost:80
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379
- **Queue Worker**: Arka planda php artisan queue:work sürekli çalışır.
- **Scheduler**: Her dakika php artisan schedule:run çalıştırır.

---

## 🧪 Testleri Çalıştırma

Platformdaki tüm güvenlik, SSRF, multi-tenant izolasyonu, SEO kuralları ve uçtan uca akış testleri PHPUnit ile doğrulanır:

`ash
# Tüm testleri çalıştır:
php artisan test

# Belirli bir grubu test et:
php artisan test --filter=SsrfProtectionTest
php artisan test --filter=WorkspaceIsolationTest
php artisan test --filter=SeoRulesTest
php artisan test --filter=UserWorkflowTest
`

---

## 👥 Rol ve Yetki Matrisi (RBAC)

| Yetenek / Eylem | Workspace Owner | Workspace Admin | Specialist | Viewer |
|---|:---:|:---:|:---:|:---:|
| Çalışma Alanını Silme / İsim Değiştirme | ✅ | ❌ | ❌ | ❌ |
| Üye Davet Etme / Rol Değiştirme | ✅ | ✅ | ❌ | ❌ |
| Proje Ekleme / Silme | ✅ | ✅ | ❌ | ❌ |
| Tarama Başlatma / İptal Etme | ✅ | ✅ | ✅ | ❌ |
| Görev Oluşturma / Durum Güncelleme | ✅ | ✅ | ✅ | ❌ |
| Rapor İndirme (PDF / CSV) | ✅ | ✅ | ✅ | ✅ |
| Proje ve Tarama Sonuçlarını İnceleme | ✅ | ✅ | ✅ | ✅ |

---

## 📂 Dokümantasyon Belgeleri

- [Mimari ve Veri Modeli Kılavuzu](docs/architecture.md)
- [Sunucu Dağıtımı, Yedekleme ve Yükseltme Yönergesi](docs/deployment.md)
