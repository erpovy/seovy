# Laravel SEO Platformu — Ana Proje Talimatı ve Yapılacaklar

> Kullanım: Bu dosyanın tamamını projeyi geliştirecek yapay zekaya ver. Bu dosya ürün kapsamını, çalışma kurallarını ve kabul kriterlerini tanımlar. Dosyanın hazırlanması uygulama geliştirmesinin başladığı anlamına gelmez.

## 1. Görev ve hedef

Modern tasarımlı, kullanıcı dostu, Laravel tabanlı ve kendi sunucumda çalıştırabileceğim kapsamlı bir SEO platformu geliştir. Platform; web sitelerini taramak, teknik SEO sorunlarını belirlemek, sayfa içi SEO çalışmalarını yönetmek, arama performansını takip etmek ve müşterilere rapor sunmak için kullanılacak.

İlk kullanım senaryosu kendi SEO çalışmalarımı yürütmek olsa da mimari, ileride çok müşterili bir SaaS hizmeti olarak satılmaya ve ayrı sunuculara kurulabilen bir ürün olarak dağıtılmaya uygun olsun. Her müşteri yalnızca kendi çalışma alanındaki verileri görsün. Ürün, vitrin ekranlarından ibaret olmayan, veritabanına bağlı ve uçtan uca çalışan bir uygulama olmalı.

“Eksiksiz” ifadesini bu dosyadaki kapsam ve kabul kriterleri olarak yorumla. Ücretli veri sağlayıcıları olmadan gerçek backlink indeksi, arama hacmi veya sınırsız Google sıralama verisi üretebileceğini varsayma. Sağlanamayan veri için uydurma değer gösterme.

## 2. Çalışma kuralları

- Önce mevcut depoyu ve varsa proje talimatlarını incele. Mevcut kullanıcı dosyalarını izinsiz silme veya üzerine yazma.
- Uygulamaya başlamadan önce kısa mimari plan, veri modeli, bağımlılıklar ve aşama sıralaması oluştur; ardından geliştirmeye devam et.
- Teknik ve geri alınabilir kararları makul varsayımlarla al; varsayımları belgele. Yalnızca kritik ve çözülemeyen belirsizliklerde soru sor.
- Geliştirme tarihindeki desteklenen kararlı Laravel, PHP ve bağımlılık sürümlerini resmi kaynaklardan doğrula; seçilen sürümleri ve gerekçelerini kaydet.
- Her aşamada çalışan bir ürün bırak. Yapılan işleri bu dosyada işaretle; eksik veya kısmi işleri tamamlanmış gösterme.
- Gerçek işlev yerine sahte sayaç, rastgele analiz sonucu, işlevsiz buton veya yalnızca statik ekran teslim etme.
- Demo verilerini açıkça etiketle ve yalnızca demo ortamında kullan. Üretim kurulumu demo kullanıcı veya varsayılan parola içermesin.
- API anahtarı, ödeme hizmeti veya harici hesap gerektiren özelliklerde entegrasyonu hazırla; yapılandırma yoksa anlaşılır bir durum göster. Kullanıcının ücretli hesap açtığını varsayma.
- Tamamlanamayan işleri, sebeplerini ve kalan adımları açıkça raporla. Yalnızca arayüzü tamamlanan bir modülü bitmiş sayma.

## 3. Teknik mimari ve kurulum hedefi

- [x] Laravel tabanlı, modüler ve bakımı kolay bir monolit oluştur. Gereksiz mikroservis karmaşıklığından kaçın.
- [x] Birincil arayüzü Vue 3 + TypeScript + Inertia.js + Tailwind CSS ile geliştir.
- [x] Birincil üretim veritabanı olarak PostgreSQL, kuyruk ve önbellek için Redis kullan. Başka motorlar için doğrulanmamış uyumluluk iddiasında bulunma.
- [x] Tarama, raporlama, veri senkronizasyonu ve e-posta işlemlerini kuyruklara taşı; uzun süren işleri HTTP isteği içinde çalıştırma.
- [x] Linux + Nginx + PHP-FPM + PostgreSQL + Redis dağıtımını belgele. Docker Compose seçeneği ve Docker kullanmadan kurulum yönergesi sağla.
- [x] Cron/scheduler, queue worker, dosya izinleri, HTTPS, depolama ve e-posta gereksinimlerini açıkça belirt. Yalnızca dosya yüklenebilen paylaşımlı hosting için destek sözü verme.
- [x] Ortam değişkenleri için açıklamalı `.env.example`, kilitli bağımlılık dosyaları ve tekrarlanabilir build komutları sun.
- [x] Temel modülleri ayır: kimlik ve çalışma alanları, projeler, tarayıcı, analiz, entegrasyonlar, raporlar, abonelikler ve yönetim.

## 4. Kullanıcılar, veri izolasyonu ve SaaS altyapısı

- [ ] Kayıt, giriş, çıkış, e-posta doğrulama, parola sıfırlama, profil düzenleme ve güvenli oturum yönetimi oluştur.
- [ ] İsteğe bağlı iki faktörlü doğrulama ve aktif oturumları sonlandırma desteği ekle.
- [ ] Çalışma alanı/organizasyon modelini kullan. Bir kullanıcı birden çok çalışma alanına üye olabilsin; yeni kullanıcı için kişisel çalışma alanı oluşturulsun.
- [ ] Çalışma alanı sahibi, yönetici, uzman ve salt okunur rollerini; süreli ve tek kullanımlık davetleri uygula.
- [ ] Platform yöneticisini çalışma alanı rollerinden ayır. Yönetici işlemlerini denetim günlüğüne yaz.
- [ ] Projeler, URL'ler, taramalar, anahtar kelimeler, raporlar, dosyalar ve entegrasyon kimlik bilgileri çalışma alanına bağlı olsun.
- [ ] İzolasyonu yalnızca arayüz filtresiyle değil, sunucuda policy, sorgu kapsamı ve kaynak sahipliği kontrolleriyle uygula.
- [ ] Kuyruk işleri, önbellek anahtarları, dışa aktarımlar ve dosya indirmelerinde de çalışma alanı sınırlarını koru.
- [ ] Kullanıcı ve çalışma alanı silme, veri dışa aktarma ve yapılandırılabilir veri saklama süreleri oluştur.

## 5. Tasarım ve kullanıcı deneyimi

- [ ] Özgün, modern ve tutarlı bir tasarım sistemi kur: renkler, tipografi, boşluklar, form alanları, tablolar, kartlar ve grafikler.
- [ ] Türkçe varsayılan dil, İngilizce çeviri altyapısı, açık/koyu tema ve masaüstü/tablet/mobil uyumluluğu sağla.
- [ ] Klavye kullanımı, görünür odak, yeterli kontrast, form etiketleri ve ekran okuyucu desteğini gözet.
- [ ] İlk kullanım akışı: çalışma alanı oluştur → site ekle → tarama sınırlarını seç → ilk taramayı başlat → bulguları incele.
- [ ] Kontrol panelinde son taramalar, kritik sorunlar, görevler ve gerçek veriden türeyen trendler göster.
- [ ] Tablolarda arama, filtreleme, sıralama, sayfalama ve uygun toplu işlemler sun.
- [ ] Yükleniyor, boş, başarısız, yetkisiz, kota dolu ve entegrasyon eksik durumlarını tasarla.
- [ ] Tarama ilerlemesini, iş durumunu ve hataları yenileme gerektirmeden veya kontrollü polling ile göster.
- [ ] Her SEO bulgusunda açıklama, etkilenen URL'ler, kanıt, önem seviyesi ve uygulanabilir çözüm önerisi bulunsun.

## 6. Proje ve web sitesi yönetimi

- [ ] Site ekleme, düzenleme, arşivleme ve silme; bir çalışma alanında birden fazla site yönetimi sağla.
- [ ] Alan adı, başlangıç URL'si, hedef ülke/dil, saat dilimi ve isteğe bağlı rakip siteleri kaydet.
- [ ] Tarama kapsamı, alt alan adı politikası, dahil/hariç URL desenleri, maksimum derinlik ve sayfa sayısı tanımlanabilsin.
- [ ] Tekrarlanan tarama takvimi, geçmiş taramalar ve iki tarama arasında yeni/çözülen/devam eden sorun karşılaştırması sun.
- [ ] Yoğun ve düzenli taramalar için site sahipliği doğrulama desteği oluştur; yetkili kullanım sınırlarını arayüzde açıkla.

## 7. Site tarayıcısı ve teknik SEO analizi

- [ ] Başlangıç URL'si, dahili bağlantılar ve sitemap üzerinden keşif yapan gerçek bir HTTP tarayıcısı geliştir.
- [ ] URL normalizasyonu, fragment temizliği, parametre politikası, yinelenen URL önleme ve tarama tuzaklarına karşı limitler uygula.
- [ ] Robots.txt kuralları ve sitemap/sitemap index dosyalarını işle; sitemap ile bulunan URL'leri karşılaştır.
- [ ] Alan adı başına hız ve eşzamanlılık sınırı, zaman aşımı, maksimum cevap boyutu, yeniden deneme ve 429 için geri çekilme uygula.
- [ ] Tarama başlatma, duraklatma, devam ettirme ve iptal işlemleri güvenilir olsun; tekrar çalışan işler mükerrer kayıt üretmesin.
- [ ] HTTP durumları, bozuk bağlantılar, yönlendirme zincirleri/döngüleri, HTTPS ve karışık içerik sorunlarını belirle.
- [ ] Title, meta description, H1–H6, canonical, meta robots, X-Robots-Tag, hreflang ve HTML dilini incele.
- [ ] Eksik/yinelenen başlık ve açıklama, başlık hiyerarşisi ve canonical tutarsızlıklarını raporla. Uzunluk eşiklerini yapılandırılabilir öneriler olarak sun.
- [ ] Dahili/harici bağlantılar, anchor metinleri, nofollow/sponsored/ugc nitelikleri ve bağlantı derinliğini göster.
- [ ] Görsel alt metni, boyut belirtilmesi ve ölçülebilen dosya büyüklüğü sorunlarını belirle.
- [ ] İçerik benzerliği, olası ince içerik ve yinelenen sayfalar için açıklanabilir yöntemler uygula; bunları kesin sıralama faktörü gibi sunma.
- [ ] JSON-LD verisini çıkar; sözdizimi ve temel alan kontrolleri yap. Resmi rich result uygunluğu garantisi verme.
- [ ] Sitemap URL'leri, canonical hedefleri ve indekslenebilirlik sinyalleri arasındaki uyuşmazlıkları raporla.
- [ ] Sahipsiz sayfa tespitinin yalnızca tarama dışı URL kaynağıyla karşılaştırma yapıldığında mümkün olduğunu belirt; kaynak eksikse kesin sonuç gösterme.
- [ ] Her bulguyu kural kodu, seviye, kanıt ve tarama tarihiyle sakla. Sağlık puanının formülünü belgeleyip kullanıcıya açıklanabilir tut.
- [ ] İlk sürümde sunucudan gelen HTML'yi analiz et; JavaScript render edilmemesinin sonuçlara etkisini göster. Headless taramayı ek kapsam olarak ayır.

## 8. Sayfa içi SEO ve içerik çalışma alanı

- [ ] Tek URL analiz ekranı ve Google sonuç görünümünü yaklaşık olarak gösteren title/description önizlemesi oluştur.
- [ ] Odak anahtar kelime, ikincil kelimeler, arama niyeti, hedef URL ve içerik notları tutulabilsin.
- [ ] İçerik brief'i, kontrol listesi, editoryal durum, sorumlu kişi ve hedef tarih yönetimi sun.
- [ ] İç bağlantı önerilerini mevcut tarama verilerine dayandır; önerinin hangi veriden çıktığını göster.
- [ ] Sorunları göreve dönüştür; açık/devam ediyor/tamamlandı/yoksayıldı durumları, yorum ve öncelik ekle.
- [ ] AI içerik önerileri isteğe bağlı olsun; çekirdek analiz AI anahtarı olmadan çalışsın. Sağlayıcı, maliyet sınırı ve kullanıcı onayıyla içerik üret; otomatik yayımlama yapma.

## 9. Arama performansı ve veri entegrasyonları

- [ ] Google Search Console OAuth bağlantısı: mülk seçimi, tarih aralığı, sorgu/sayfa/ülke/cihaz kırılımları, tıklama, gösterim, CTR ve ortalama konum.
- [ ] GSC verisini sayfalama, API kotası, gecikme ve yeniden senkronizasyon kurallarıyla sakla. Ortalama konumu anlık SERP sırası olarak etiketleme.
- [ ] Google Analytics 4 bağlantısı: seçilen mülk için organik trafik ve açılış sayfası metrikleri; ölçümlerin kaynağını belirt.
- [ ] PageSpeed Insights entegrasyonu: mobil/masaüstü laboratuvar verisi, varsa CrUX alan verisi ve geçmiş ölçümler. Bu iki veri türünü ayrı göster.
- [ ] OAuth token yenileme, bağlantı kaldırma, yetki hatası ve kota aşımı senaryolarını ele al; sırları şifreli sakla.
- [ ] Anahtar kelime listeleri, etiketler, hedef sayfalar, ülke/dil/cihaz ve geçmiş ölçüm kayıtları oluştur.
- [ ] Sıra takibi, arama hacmi ve backlink verisi için değiştirilebilir sağlayıcı arayüzleri geliştir. En az bir gerçek sağlayıcının dokümante edilmiş adaptörünü ve CSV içe aktarma yolunu hazırla.
- [ ] API anahtarı yokken sağlayıcıya bağlı ölçümler devre dışı olsun; GSC ve yerel veriler kullanılabilsin. Yetkisiz arama motoru scraping'ine bağımlı olma.
- [ ] Backlink ekranında kaynağı bulunan kayıtlar için kaynak/hedef URL, anchor, first/last seen ve link niteliğini göster. Toxicity gibi öznel puanları açıklamasız gerçek olarak sunma.
- [ ] Rakip karşılaştırmalarını erişilebilir verilerle sınırla; üçüncü taraf sitelerin özel analitik verilerine erişildiği izlenimi verme.

## 10. Raporlar ve bildirimler

- [ ] Proje, tarih aralığı ve seçilen modüllerle rapor üret; aynı veriyi CSV ve PDF olarak dışa aktar.
- [ ] Büyük raporları kuyrukta üret; dosyalar yetkilendirilmiş ve süreli indirme bağlantılarıyla alınsın.
- [ ] Planlanan raporlar, tamamlanan/başarısız taramalar ve kritik sorun değişiklikleri için uygulama içi/e-posta bildirimleri oluştur.
- [ ] Müşteri raporlarında logo ve çalışma alanı marka bilgileri kullanılabilsin.
- [ ] Paylaşılabilir rapor bağlantıları isteğe bağlı, tahmin edilemez, süreli ve iptal edilebilir olsun.

## 11. Ticari kullanım ve abonelik hazırlığı

- [ ] Aynı kod tabanında iki kurulum modu destekle: kendi sunucusunda bağımsız kullanım ve çok müşterili SaaS işletimi.
- [ ] Bağımsız kurulum için zorunlu dış lisans sunucusu veya abonelik bağlantısı gerektirme.
- [ ] Plan bazında proje, ekip üyesi, aylık taranan sayfa, anahtar kelime ve veri saklama limitleri tanımla.
- [ ] Limitleri yalnızca arayüzde değil servis ve kuyruk katmanında atomik olarak uygula; eşzamanlı işlerle aşılmasını engelle.
- [ ] Deneme süresi, plan değişikliği, iptal, ödeme başarısızlığı ve salt okunur erişim davranışlarını açıkça tanımla.
- [ ] Ödeme sağlayıcısını değiştirilebilir tut; ilk adaptörü Stripe test modu üzerinden uygula. Canlı kullanım ülke ve hesap uygunluğu sonradan yapılandırılabilsin.
- [ ] Webhook imzası doğrulama, olayların tekrar işlenmesine dayanıklılık ve abonelik durum eşleştirmesi oluştur.
- [ ] Yönetim panelinde kullanıcılar, çalışma alanları, planlar, kullanım, başarısız işler ve sistem sağlığı görülsün.
- [ ] Gerçek ödeme, vergi/fatura mevzuatı, satış sözleşmeleri ve ticari lisans politikası ayrı operasyonel konulardır; teknik hazırlığı hukuki uygunluk garantisi gibi sunma.

## 12. Güvenlik ve operasyon

- [ ] CSRF, XSS, SQL injection, IDOR, brute force ve yetki yükseltmeye karşı Laravel mekanizmalarını doğru kullan.
- [ ] Tarayıcıda SSRF koruması uygula: yalnızca HTTP/HTTPS, izinli portlar, genel IP kontrolü, private/loopback/link-local/metadata adreslerini engelleme, IPv4/IPv6 kontrolü ve DNS rebinding savunması.
- [ ] Her yönlendirme ve bağlantı aşamasında hedefi yeniden doğrula; uygulama dışı ağ erişim kısıtlarını da dağıtım yönergesine ekle.
- [ ] Uzak sayfaların HTML, script ve başlıklarını güvenilmeyen veri kabul et; içerikleri arayüzde güvenli göster, script çalıştırma.
- [ ] CSV formula injection, dosya yükleme boyutu/türü, path traversal ve rapor üreticisinden ağ erişimi risklerini ele al.
- [ ] Sırların loglarda, hata ekranlarında, istemci kodunda veya kaynak kontrolünde görünmesini önle.
- [ ] Yapılandırılmış loglar, denetim izi, health check, queue izleme ve anlamlı hata mesajları oluştur.
- [ ] Yedekleme, geri yükleme, retention ve sürüm yükseltme prosedürü yaz; geri yükleme denemesi yap.

## 13. Bir kez kullanılan kurulum sihirbazı

- [ ] Tarayıcıdan açılan `/install` akışı ve otomasyon için eşdeğer `php artisan app:install` komutu oluştur.
- [ ] Sihirbazın sunucu paketlerini kendiliğinden kuracağını varsayma; PHP, uzantılar, veritabanı, Redis, gerekli dizin izinleri ve yapılandırmayı kontrol et.
- [ ] İlk erişimi sunucu tarafında üretilen tek kullanımlık kurulum anahtarıyla koru; internete açık kurulum ekranını başkasının sahiplenmesini engelle.
- [ ] Dil, uygulama URL'si, veritabanı, Redis, e-posta, yönetici hesabı ve kurulum modu adımlarını sun.
- [ ] Bağlantıları doğrula; uygulama anahtarını bir kez oluştur; migration ve zorunlu başlangıç kayıtlarını çalıştır. Mevcut veritabanını otomatik sıfırlama.
- [ ] Ortam dosyasını güvenli ve atomik yaz; parolaları ekranda/loglarda açığa çıkarma.
- [ ] Kesinti veya tekrar denemede güvenli davran; yarım kalan kurulumu açıklayıcı hatayla devam ettirebil. Eşzamanlı iki kurulumu engelle.
- [ ] Başarıdan sonra kalıcı kurulum kilidi oluştur ve kurulum uç noktalarını kapat. Sonraki kullanımda kurulum dosyası/anahtarı gerekmeyecek şekilde tasarla.
- [ ] Kilit kaldırma ve yeniden kurulum yalnızca sunucu yöneticisinin açık CLI işlemiyle mümkün olsun; yanlışlıkla veri kaybı oluşturmasın.
- [ ] Uygulama ayarları panelden yönetilebilsin; altyapı sırlarının yönetimini güvenli tut.
- [ ] Ayrı yükseltme komutu/yönergesi sun; güncelleme sırasında yeniden kurulum veya APP_KEY yenileme yapma.

## 14. Performans hedefleri ve doğrulama yöntemi

Referans ortam: 4 vCPU, 8 GB RAM, SSD; Linux, PostgreSQL ve Redis. Donanım, yazılım sürümleri, veri büyüklüğü ve test komutları sonuçlarla birlikte kaydedilsin.

- [ ] Tarama için kontrollü yerel test sitesi kullan: 10.000 HTML sayfası, sayfa başına yaklaşık 50 KB içerik, 50 ms sabit yanıt gecikmesi ve belgelenmiş bağlantı grafiği. Kuyruk keşfi, yinelenen URL ve hata senaryolarını da test et.
- [ ] Bu veri setinde 10 eşzamanlı istek ve saniyede en fazla 10 istek ayarıyla taramayı hedef olarak 30 dakika içinde bitir; 429, timeout ve büyük yanıt testlerini ayrı raporla.
- [ ] Tarama belleğini sayfa sayısıyla sınırsız büyütme; worker başına 256 MB bellek sınırında tamamlanmasını doğrula.
- [ ] 100.000 sayfa kaydı ve 10 eşzamanlı kullanıcıyla, önbellek ısındıktan sonra 5 dakikalık testte temel liste ve özet isteklerinde p95 sunucu yanıt süresini 500 ms altında hedefle. Rapor oluşturma ve harici API çağrılarını bu ölçümden ayır.
- [ ] Büyük tablolarda sunucu tarafı sayfalama, gerekli indeksler, toplu yazma ve N+1 kontrolü uygula.
- [ ] Hedefler sağlanmadığında gerçek sonucu ve darboğazı raporla; ölçmeden “tam performanslı” veya “sınırsız” iddiasında bulunma.

## 15. Testler ve somut kabul kriterleri

- [ ] Temiz ortamda README adımlarıyla kurulum tamamlanabiliyor; yönetici oluşturuluyor ve ikinci kurulum denemesi engelleniyor.
- [ ] İki ayrı çalışma alanındaki kullanıcılar birbirlerinin proje, URL, rapor, dosya veya API kayıtlarına erişemiyor. Doğrudan ID değiştirerek erişim denemeleri test ediliyor.
- [ ] Yeni kullanıcı kayıt olup bir proje ekleyebiliyor, gerçek tarama başlatabiliyor, bulguyu göreve dönüştürebiliyor ve rapor indirebiliyor.
- [ ] Sabit test sitesindeki bilinen eksik title, 404, redirect loop, noindex, canonical ve robots kuralları beklenen sonuçları veriyor.
- [ ] İptal edilen tarama yeni iş üretmiyor; tekrar çalışan queue işi mükerrer bulgu/URL/kullanım kaydı oluşturmuyor.
- [ ] Paralel taramalarda plan limiti aşılamıyor; başarısız işler belgelenen kullanım sayım kuralına uyuyor.
- [ ] SSRF testleri localhost, private IPv4/IPv6, metadata IP, DNS değişimi ve public URL'den private hedefe yönlendirmeyi kapsıyor.
- [ ] Entegrasyonlarda başarı, token yenileme, bağlantı kaldırma, kota ve hata senaryoları sözleşme testleriyle doğrulanıyor. Mock testleri canlı API doğrulaması olarak sunulmuyor.
- [ ] Kurulum kilidi, yetkilendirme, webhook tekrarları, zamanlanmış işler ve rapor indirme yetkileri otomatik testlerle korunuyor.
- [ ] Kritik kullanıcı akışları için uçtan uca tarayıcı testleri; analiz kuralları için unit ve servisler için integration testleri ekleniyor.
- [ ] Mobil görünüm, klavye erişimi, boş/hata durumları ve tema görsel olarak kontrol ediliyor.
- [ ] Test, lint, typecheck ve production build komutları geçiyor; bilinen başarısızlıklar açıkça listeleniyor.

## 16. Uygulama sırası

1. Depo inceleme, mimari kararlar, veri modeli ve geliştirme ortamı.
2. Kimlik doğrulama, çalışma alanları, yetkilendirme ve tasarım sistemi.
3. Projeler, güvenli tarayıcı, analiz kuralları ve tarama arayüzü.
4. İçerik çalışma alanı, görevler, karşılaştırmalar ve raporlar.
5. GSC, GA4, PageSpeed ve sağlayıcı adaptörleri.
6. SaaS limitleri, ödeme test entegrasyonu ve yönetim paneli.
7. Kurulum sihirbazı, dağıtım, yükseltme ve yedekleme.
8. Güvenlik, performans, uçtan uca doğrulama ve son dokümantasyon.

## 17. Teslim edilecekler

- [ ] Çalışan uygulamanın kaynak kodu ve veritabanı migration'ları.
- [ ] Kurulum sihirbazı, CLI kurulumu, Docker Compose ve örnek sunucu yapılandırmaları.
- [ ] README: yerel geliştirme, üretim kurulumu, queue/scheduler, e-posta, entegrasyonlar ve sorun giderme.
- [ ] Mimari/veri modeli belgesi, rol matrisi ve API varsa endpoint/yetkilendirme belgesi.
- [ ] Kullanıcı kılavuzu, yönetici kılavuzu, yedekleme/geri yükleme ve güncelleme yönergeleri.
- [ ] Test komutları, sonuçları, performans raporu ve doğrulama ortamı.
- [ ] Demo veri seti ve üretimden ayrı demo başlatma yöntemi.
- [ ] Özellik durum matrisi: tamamlandı / kısmi / harici yapılandırma bekliyor / yapılmadı; her durum için kanıt ve kalan iş.
- [ ] Üçüncü taraf servislerin maliyet/kota bağımlılıkları, bilinen sınırlamalar ve sonraki sürüm önerileri.

## 18. Yapay zekalar arasında karşılaştırma

Her denemeye aynı boş depo veya aynı başlangıç commit'i, aynı talimat, aynı süre/token bütçesi, aynı donanım ve aynı API erişimi verilsin. Kullanılan model, tarih, süre, token/maliyet bilgisi mevcutsa ve ek kullanıcı müdahaleleri kaydedilsin. Puanlama, modelin kendi beyanına değil çalıştırılabilir kanıta dayansın.

| Ölçüt | Puan | Beklenen kanıt |
|---|---:|---|
| Gerçek işlevsellik ve kapsam | 25 | Çalışan kullanıcı akışları ve özellik durum matrisi |
| Güvenlik ve veri izolasyonu | 20 | Yetkilendirme, SSRF ve sır yönetimi testleri |
| Modern tasarım ve kullanım kolaylığı | 15 | Masaüstü/mobil inceleme ve tamamlanabilir akışlar |
| Kod kalitesi ve mimari | 10 | Modülerlik, okunabilirlik, veri modeli ve hata yönetimi |
| Performans ve kuyruk güvenilirliği | 10 | Aynı veri setindeki ölçümler ve tekrar deneme testleri |
| Kurulum ve işletim kolaylığı | 10 | Temiz ortam kurulumu, kilit, yükseltme ve geri yükleme |
| Testler ve dokümantasyon | 10 | Tekrarlanabilir test/build ve kullanılabilir belgeler |
| **Toplam** | **100** | |

Veri izolasyonu ihlali, çalışmayan temiz kurulum, güvenli olmayan SSRF erişimi veya gerçekmiş gibi gösterilen sahte SEO sonuçları varsa toplam puandan bağımsız olarak ürün “üretime hazır değil” kabul edilsin. Ücretli API anahtarı bulunmaması tek başına başarısızlık sayılmasın; adaptör kalitesi, hata yönetimi ve eksik yapılandırmanın doğru sunulması değerlendirilsin.

## 19. İlk sürüm dışında tutulan işler

Kendi küresel backlink indeksini kurmak, sınırsız SERP scraping, otomatik link satın alma, otomatik site içeriği yayımlama, yerel işletme dizinlerine toplu kayıt, native mobil uygulamalar, ileri seviye log analizi ve headless JavaScript taraması bu teslimatın zorunlu kapsamı değildir. Bu işler ancak ayrı gereksinim, bütçe ve kabul kriterleriyle sonraki sürümlere eklenir.
