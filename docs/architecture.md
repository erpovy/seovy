# Seovy — Mimari ve Modül Tasarım Belgesi

Seovy; web sitelerini taramak, teknik SEO sorunlarını saptamak, sayfa içi SEO ve görevleri yönetmek, arama performansını izlemek ve raporlamak üzere tasarlanmış **Laravel 12 tabanlı modüler bir monolittir**.

---

## 1. Mimari Prensipler

1. **Modüler Monolit**: Mikroservislerin dağıtık işlem ve ağ gecikmesi karmaşıklığından kaçınırken, etki alanları (domain boundary) net ayrılmış servisler halinde organize edilir.
2. **Kuyruk Öncelikli Asenkron İşleme**: Tarama istekleri, robots/sitemap ayrıştırma, sayfa analizi, PDF üretimi ve e-posta gönderimleri arka plan kuyruklarında (`redis`) çalışır. HTTP istekleri hiçbir zaman uzun süren tarama işlemlerini bloklamaz.
3. **Katı Çok Kiracılı (Multi-Tenant) İzolasyon**: Veriler doğrudan `Workspace` (Çalışma Alanı) varlığına bağlanır. Tüm sorgular `BelongsToWorkspace` trait'i, global scope'lar ve Laravel Policy sınıfları üzerinden süzülür. Kullanıcılar yalnızca üye oldukları çalışma alanlarına erişebilir.
4. **Çift Kurulum Modu (Single-Tenant vs Multi-Tenant SaaS)**: Kod tabanı, `APP_INSTALL_MODE=self_hosted` olduğunda tek kullanıcılı/kurumsal modda sınırsız kaynakla çalışabilir; `APP_INSTALL_MODE=saas` olduğunda ise plan bazlı kota kontrolleri ve Stripe ödeme katmanını devreye alır.

---

## 2. Temel Modüller

```
app/
├── Modules/
│   ├── Auth/              # Kimlik doğrulama, iki faktörlü koruma, oturumlar
│   ├── Workspace/         # Çalışma alanları, roller (Owner, Admin, Specialist, Viewer), davetler
│   ├── Project/           # Web sitesi / proje yönetimi, tarama ayarları, sahiplik doğrulama
│   ├── Crawler/           # SSRF korumalı HTTP istemcisi, robots.txt, sitemap ayrıştırıcı, kuyruk yöneticisi
│   ├── Analyzer/          # 25+ teknik SEO analiz kuralı, sağlık skoru motoru, bulgular
│   ├── ContentWorkspace/  # Sayfa içi analiz, SERP önizleme, görevler (tasks)
│   ├── Integrations/      # Google Search Console, Google Analytics 4, PageSpeed Insights, SERP adaptörleri
│   ├── Reports/           # PDF denetim raporu üretimi, CSV dışa aktarımı, süreli güvenli linkler
│   ├── Billing/           # Planlar, kotalar, Stripe test entegrasyonu, webhook'lar
│   └── Admin/             # Platform yönetim paneli, denetim izi (audit log), sistem sağlığı
```

---

## 3. Rol ve Yetki Matrisi (Workspace RBAC)

| Eylem | Owner (Sahip) | Admin (Yönetici) | Specialist (Uzman) | Viewer (Salt Okunur) |
|---|:---:|:---:|:---:|:---:|
| Çalışma Alanını Silme/Aktarma | ✓ | ✗ | ✗ | ✗ |
| Ekip Üyesi Ekleme / Rol Değiştirme | ✓ | ✓ | ✗ | ✗ |
| Plan / Fatura Yönetimi (SaaS) | ✓ | ✓ | ✗ | ✗ |
| Proje Ekleme / Silme / Ayarlama | ✓ | ✓ | ✓ | ✗ |
| Tarama Başlatma / Durdurma / İptal | ✓ | ✓ | ✓ | ✗ |
| Görev Açma / Güncelleme | ✓ | ✓ | ✓ | ✗ |
| Bulguları ve Raporları İnceleme | ✓ | ✓ | ✓ | ✓ |
| Rapor PDF / CSV İndirme | ✓ | ✓ | ✓ | ✓ |

Platform Yöneticisi (`is_platform_admin = true`) rolü ise çalışma alanı rollerinden tamamen ayrı olup tüm sistemi denetleme ve platform genelinde yönetim yetkisine sahiptir.
