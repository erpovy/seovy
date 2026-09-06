<?php

namespace App\Modules\Analyzer;

use App\Modules\Crawler\SafeHttpResponse;
use App\Modules\Crawler\UrlNormalizer;
use Symfony\Component\DomCrawler\Crawler;

class SeoRuleEngine
{
    /**
     * Analyze a crawled page response and return extracted metadata, links, and SEO findings.
     *
     * @return array{page_data: array, findings: array, discovered_urls: array}
     */
    public function analyze(SafeHttpResponse $response, string $projectDomain, array $crawlSettings = []): array
    {
        $url = $response->url;
        $statusCode = $response->statusCode;
        $headers = $response->headers;
        $body = $response->body;
        $contentType = $response->getContentType();

        $findings = [];
        $discoveredUrls = [];

        // 1. HTTP Status & Network Rules
        if ($statusCode >= 400 && $statusCode < 500) {
            $findings[] = [
                'rule_code' => 'STATUS_4XX_CLIENT_ERROR',
                'category' => 'crawlability',
                'severity' => 'critical',
                'title' => "Bozuk veya Bulunamayan Sayfa ({$statusCode})",
                'description' => "Sayfa {$statusCode} istemci hatası kodu döndürdü. Arama motorları bu sayfaları dizinden kaldırabilir ve kullanıcı deneyimi zarar görür.",
                'evidence' => ['status_code' => $statusCode, 'url' => $url],
                'recommendation' => 'Bağlantıyı güncelleyin veya kalıcı olarak kaldırılan içerikler için uygun sayfaya 301 yönlendirmesi yapın.',
            ];
        } elseif ($statusCode >= 500) {
            $findings[] = [
                'rule_code' => 'STATUS_5XX_SERVER_ERROR',
                'category' => 'crawlability',
                'severity' => 'critical',
                'title' => "Sunucu Hatası ({$statusCode})",
                'description' => "Sunucu {$statusCode} hatası verdi. Tarayıcı botlar sayfaya erişemedi.",
                'evidence' => ['status_code' => $statusCode, 'url' => $url],
                'recommendation' => 'Sunucu loglarını kontrol edin, veritabanı veya PHP arka plan hatalarını giderin.',
            ];
        }

        // Redirect loop or chain check
        if (!empty($response->redirectHistory)) {
            $redirectCount = count($response->redirectHistory);
            if ($redirectCount > 1) {
                $findings[] = [
                    'rule_code' => 'REDIRECT_CHAIN',
                    'category' => 'crawlability',
                    'severity' => 'warning',
                    'title' => "Yönlendirme Zinciri Tespit Edildi ({$redirectCount} Adım)",
                    'description' => 'Sayfaya ulaşmak için birden fazla ardışık yönlendirme yapıldı. Bu durum tarama bütçesini tüketir ve sayfa açılışını yavaşlatır.',
                    'evidence' => ['history' => $response->redirectHistory],
                    'recommendation' => 'İlk URL\'yi doğrudan nihai hedef URL\'ye tek bir 301 yönlendirmesiyle bağlayın.',
                ];
            }
        }

        // HTTPS Protocol check
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            $findings[] = [
                'rule_code' => 'SECURITY_NON_HTTPS',
                'category' => 'security',
                'severity' => 'critical',
                'title' => 'Güvensiz HTTP Bağlantısı',
                'description' => 'Sayfa şifrelenmemiş HTTP protokolü üzerinden sunuluyor. HTTPS temel bir sıralama ve güvenlik sinyalidir.',
                'evidence' => ['url' => $url],
                'recommendation' => 'Sayfayı HTTPS sürümüne 301 yönlendirmesiyle yönlendirin ve SSL sertifikasını doğrulayın.',
            ];
        }

        // Non-HTML content check (e.g. PDF, Image, etc.)
        if (!str_contains($contentType, 'text/html') && !empty($contentType)) {
            return [
                'page_data' => [
                    'url' => $url,
                    'url_hash' => UrlNormalizer::getHash($url),
                    'path' => parse_url($url, PHP_URL_PATH) ?: '/',
                    'status_code' => $statusCode,
                    'content_type' => $contentType,
                    'response_time_ms' => $response->responseTimeMs,
                    'title' => null,
                    'meta_description' => null,
                    'canonical_url' => null,
                    'robots_meta' => null,
                    'is_indexable' => $statusCode === 200,
                    'h1_count' => 0,
                    'h1_tags' => [],
                    'depth' => 0,
                    'inlinks_count' => 0,
                    'outlinks_count' => 0,
                    'word_count' => 0,
                    'schema_types' => [],
                    'raw_headers' => $headers,
                    'content_hash' => hash('sha256', $body),
                ],
                'findings' => $findings,
                'discovered_urls' => [],
            ];
        }

        // Parse HTML with Symfony DomCrawler
        $dom = new Crawler($body, $url);

        // 2. Title Analysis
        $titleNodes = $dom->filter('head > title, title');
        $title = $titleNodes->count() > 0 ? trim($titleNodes->text()) : '';
        $titleLength = mb_strlen($title);

        if (empty($title)) {
            $findings[] = [
                'rule_code' => 'TITLE_MISSING',
                'category' => 'meta',
                'severity' => 'critical',
                'title' => 'Sayfa Başlığı (Title) Eksik',
                'description' => 'Sayfada <title> etiketi bulunamadı veya içi boş.',
                'evidence' => ['title' => ''],
                'recommendation' => 'Sayfanın konusunu ve odak anahtar kelimesini içeren 50-60 karakterlik özgün bir başlık ekleyin.',
            ];
        } elseif ($titleLength < 30) {
            $findings[] = [
                'rule_code' => 'TITLE_TOO_SHORT',
                'category' => 'meta',
                'severity' => 'notice',
                'title' => "Sayfa Başlığı Çok Kısa ({$titleLength} Karakter)",
                'description' => 'Başlık 30 karakterden kısa olduğu için arama sonuçlarında yeterli bağlam sunamayabilir.',
                'evidence' => ['title' => $title, 'length' => $titleLength],
                'recommendation' => 'Sayfa başlığını marka veya açıklayıcı terimlerle 50-60 karakter aralığına genişletin.',
            ];
        } elseif ($titleLength > 65) {
            $findings[] = [
                'rule_code' => 'TITLE_TOO_LONG',
                'category' => 'meta',
                'severity' => 'warning',
                'title' => "Sayfa Başlığı Çok Uzun ({$titleLength} Karakter)",
                'description' => 'Başlık 65 karakterin üzerinde olduğu için Google arama sonuçlarında kesilebilir.',
                'evidence' => ['title' => $title, 'length' => $titleLength],
                'recommendation' => 'En önemli anahtar kelimeleri başa alarak başlığı 55-60 karakter seviyesine kısaltın.',
            ];
        }

        // 3. Meta Description Analysis
        $descNodes = $dom->filter('meta[name="description"], meta[name="Description"]');
        $metaDescription = $descNodes->count() > 0 ? trim($descNodes->attr('content') ?? '') : '';
        $descLength = mb_strlen($metaDescription);

        if (empty($metaDescription)) {
            $findings[] = [
                'rule_code' => 'META_DESCRIPTION_MISSING',
                'category' => 'meta',
                'severity' => 'warning',
                'title' => 'Meta Açıklama (Description) Eksik',
                'description' => 'Sayfada meta description etiketi bulunmuyor. Arama motorları sayfa içinden rastgele pasajlar seçebilir.',
                'evidence' => ['description' => ''],
                'recommendation' => 'Kullanıcıyı tıklamaya teşvik eden 120-155 karakterlik özgün bir meta açıklama yazın.',
            ];
        } elseif ($descLength < 70) {
            $findings[] = [
                'rule_code' => 'META_DESCRIPTION_TOO_SHORT',
                'category' => 'meta',
                'severity' => 'notice',
                'title' => "Meta Açıklama Çok Kısa ({$descLength} Karakter)",
                'description' => 'Meta açıklama 70 karakterden kısa.',
                'evidence' => ['description' => $metaDescription, 'length' => $descLength],
                'recommendation' => 'Açıklamayı sayfa içeriğini ve harekete geçirici mesajı kapsayacak şekilde 120-155 karaktere tamamlayın.',
            ];
        } elseif ($descLength > 165) {
            $findings[] = [
                'rule_code' => 'META_DESCRIPTION_TOO_LONG',
                'category' => 'meta',
                'severity' => 'notice',
                'title' => "Meta Açıklama Çok Uzun ({$descLength} Karakter)",
                'description' => 'Meta açıklama 165 karakterden uzun ve SERP sonuçlarında kırpılabilir.',
                'evidence' => ['description' => $metaDescription, 'length' => $descLength],
                'recommendation' => 'Açıklamayı 155 karakter altına optimize edin.',
            ];
        }

        // 4. H1 & Heading Hierarchy Analysis
        $h1Nodes = $dom->filter('h1');
        $h1Count = $h1Nodes->count();
        $h1Tags = [];
        if ($h1Count > 0) {
            $h1Nodes->each(function (Crawler $node) use (&$h1Tags) {
                $h1Tags[] = trim($node->text());
            });
        }

        if ($h1Count === 0) {
            $findings[] = [
                'rule_code' => 'H1_MISSING',
                'category' => 'content',
                'severity' => 'critical',
                'title' => 'H1 Başlığı Eksik',
                'description' => 'Sayfada hiç <h1> başlık etiketi bulunmuyor. H1, sayfanın ana konusunu belirten en temel başlık hiyerarşisidir.',
                'evidence' => ['h1_count' => 0],
                'recommendation' => 'Sayfa başına konuyu tam özetleyen tek bir <h1> etiketi ekleyin.',
            ];
        } elseif ($h1Count > 1) {
            $findings[] = [
                'rule_code' => 'H1_MULTIPLE',
                'category' => 'content',
                'severity' => 'warning',
                'title' => "Birden Fazla H1 Başlığı ({$h1Count} Adet)",
                'description' => 'Sayfada birden çok <h1> etiketi var. Bu durum sayfa konusunun hiyerarşik netliğini bozabilir.',
                'evidence' => ['h1_tags' => $h1Tags],
                'recommendation' => 'Birincil başlığı tek bir <h1> olarak koruyun, diğer alt başlıkları <h2> veya <h3> seviyesine dönüştürün.',
            ];
        }

        // 5. Canonical URL Analysis
        $canonicalNodes = $dom->filter('link[rel="canonical"], link[rel="CANONICAL"]');
        $canonicalUrl = $canonicalNodes->count() > 0 ? trim($canonicalNodes->attr('href') ?? '') : '';

        if (empty($canonicalUrl)) {
            $findings[] = [
                'rule_code' => 'CANONICAL_MISSING',
                'category' => 'indexability',
                'severity' => 'warning',
                'title' => 'Canonical Etiketi Eksik',
                'description' => 'Sayfada <link rel="canonical"> etiketi belirtilmemiş. Yinelenen parametre veya protokol versiyonlarında dizinleme karışıklığı doğabilir.',
                'evidence' => ['canonical' => null],
                'recommendation' => 'Sayfaya kendisini veya orijinal kaynağı gösteren mutlak (absolute) canonical etiketi ekleyin.',
            ];
        } else {
            // Check relative canonical
            if (!preg_match('/^https?:\/\//i', $canonicalUrl)) {
                $findings[] = [
                    'rule_code' => 'CANONICAL_RELATIVE',
                    'category' => 'indexability',
                    'severity' => 'warning',
                    'title' => 'Göreceli (Relative) Canonical URL',
                    'description' => "Canonical URL '{$canonicalUrl}' mutlak alan adı içermiyor.",
                    'evidence' => ['canonical' => $canonicalUrl],
                    'recommendation' => 'Canonical bağlantısını protokol ve domain içeren tam mutlak URL haline getirin.',
                ];
            }
        }

        // 6. Robots Meta & X-Robots-Tag Indexability
        $robotsMetaNodes = $dom->filter('meta[name="robots"], meta[name="ROBOTS"], meta[name="Robots"], meta[name="googlebot"]');
        $robotsMeta = $robotsMetaNodes->count() > 0 ? strtolower(trim($robotsMetaNodes->attr('content') ?? '')) : '';
        $xRobotsTag = strtolower($response->getHeaderLine('x-robots-tag'));

        $isIndexable = true;
        if (str_contains($robotsMeta, 'noindex') || str_contains($xRobotsTag, 'noindex')) {
            $isIndexable = false;
            $findings[] = [
                'rule_code' => 'ROBOTS_NOINDEX',
                'category' => 'indexability',
                'severity' => 'notice',
                'title' => 'Sayfa Noindex Olarak İşaretlenmiş',
                'description' => 'Sayfa meta robots veya X-Robots-Tag üzerinden `noindex` direktifi içeriyor. Arama motorları bu sayfayı dizine eklemez.',
                'evidence' => ['robots_meta' => $robotsMeta, 'x_robots_tag' => $xRobotsTag],
                'recommendation' => 'Eğer bu sayfanın Google\'da çıkması isteniyorsa `noindex` kuralını kaldırın.',
            ];
        }

        // 7. HTML Language & Hreflang
        $htmlLangNodes = $dom->filter('html[lang]');
        $htmlLang = $htmlLangNodes->count() > 0 ? trim($htmlLangNodes->attr('lang') ?? '') : '';

        if (empty($htmlLang)) {
            $findings[] = [
                'rule_code' => 'HTML_LANG_MISSING',
                'category' => 'meta',
                'severity' => 'notice',
                'title' => 'HTML Dil (lang) Niteliği Eksik',
                'description' => '<html lang="..."> niteliği eksik. Arama motorları ve ekran okuyucular sayfanın ana dilini tespit etmekte zorlanabilir.',
                'evidence' => ['lang' => ''],
                'recommendation' => '<html lang="tr"> veya uygun ISO dil kodunu ekleyin.',
            ];
        }

        // 8. Word Count & Thin Content
        // Strip scripts and styles for pure text analysis
        $textContent = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $body);
        $textContent = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $textContent);
        $cleanText = trim(strip_tags($textContent));
        $cleanWords = preg_split('/\s+/u', $cleanText, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($cleanWords);

        if ($wordCount < 150 && $statusCode === 200 && $isIndexable) {
            $findings[] = [
                'rule_code' => 'CONTENT_THIN',
                'category' => 'content',
                'severity' => 'warning',
                'title' => "Yetersiz/Zayıf İçerik ({$wordCount} Kelime)",
                'description' => 'Sayfadaki metin miktarı 150 kelimenin altında. Zayıf içerikli sayfalar organik arama sıralamalarında geride kalabilir.',
                'evidence' => ['word_count' => $wordCount],
                'recommendation' => 'Kullanıcı niyetine cevap veren zengin, bilgilendirici özgün içerik ve açıklamalar ekleyin.',
            ];
        }

        // 9. Images Analysis (Missing Alt)
        $imgNodes = $dom->filter('img');
        $missingAltCount = 0;
        $imgNodes->each(function (Crawler $node) use (&$missingAltCount) {
            $alt = $node->attr('alt');
            if ($alt === null || trim($alt) === '') {
                $missingAltCount++;
            }
        });

        if ($missingAltCount > 0) {
            $findings[] = [
                'rule_code' => 'IMAGES_MISSING_ALT',
                'category' => 'content',
                'severity' => 'warning',
                'title' => "Alt Metni Olmayan {$missingAltCount} Görsel Tespit Edildi",
                'description' => 'Sayfadaki bazı görsellerde `alt` niteliği eksik. Bu durum görsel SEO sıralamasını ve erişilebilirliği olumsuz etkiler.',
                'evidence' => ['missing_alt_count' => $missingAltCount, 'total_images' => $imgNodes->count()],
                'recommendation' => 'Görsellerin neyi temsil ettiğini açıklayan anlamlı ve anahtar kelime destekli alt metinler tanımlayın.',
            ];
        }

        // 10. Structured Data (JSON-LD)
        $jsonLdNodes = $dom->filter('script[type="application/ld+json"]');
        $schemaTypes = [];

        $jsonLdNodes->each(function (Crawler $node) use (&$schemaTypes, &$findings) {
            $jsonString = trim($node->text());
            if (!empty($jsonString)) {
                $data = json_decode($jsonString, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $findings[] = [
                        'rule_code' => 'SCHEMA_JSONLD_SYNTAX_ERROR',
                        'category' => 'indexability',
                        'severity' => 'warning',
                        'title' => 'JSON-LD Yapısal Veri Sözdizimi Hatası',
                        'description' => 'Sayfadaki script[type="application/ld+json"] geçerli bir JSON yapısına sahip değil.',
                        'evidence' => ['error' => json_last_error_msg()],
                        'recommendation' => 'JSON-LD şemasındaki tırnak, virgül veya parantez hatalarını düzeltin.',
                    ];
                } else {
                    if (isset($data['@type'])) {
                        $schemaTypes[] = is_array($data['@type']) ? implode(', ', $data['@type']) : $data['@type'];
                    } elseif (isset($data['@graph']) && is_array($data['@graph'])) {
                        foreach ($data['@graph'] as $item) {
                            if (isset($item['@type'])) {
                                $schemaTypes[] = is_array($item['@type']) ? implode(', ', $item['@type']) : $item['@type'];
                            }
                        }
                    }
                }
            }
        });

        // 11. Links Extraction & Discovery
        $aNodes = $dom->filter('a[href]');
        $inlinksCount = 0;
        $outlinksCount = 0;

        $aNodes->each(function (Crawler $node) use ($url, $projectDomain, &$discoveredUrls, &$inlinksCount, &$outlinksCount, &$findings) {
            $href = $node->attr('href');
            $resolved = UrlNormalizer::resolveRelativeUrl($url, $href);
            if (empty($resolved)) {
                return;
            }

            $isInternal = UrlNormalizer::isInternal($resolved, $projectDomain);
            if ($isInternal) {
                $inlinksCount++;
                $discoveredUrls[] = $resolved;
            } else {
                $outlinksCount++;
            }

            // Check empty anchor
            $anchorText = trim($node->text());
            if (empty($anchorText) && $node->filter('img')->count() === 0) {
                $findings[] = [
                    'rule_code' => 'LINK_EMPTY_ANCHOR',
                    'category' => 'links',
                    'severity' => 'notice',
                    'title' => 'Boş Çapa Metni (Empty Anchor Text)',
                    'description' => "Bağlantıda ({$resolved}) metin veya görsel bulunmuyor.",
                    'evidence' => ['target_url' => $resolved],
                    'recommendation' => 'Bağlantıya tıklanacak içeriği tarif eden açıklayıcı bir metin ekleyin.',
                ];
            }
        });

        $discoveredUrls = array_values(array_unique($discoveredUrls));

        return [
            'page_data' => [
                'url' => $url,
                'url_hash' => UrlNormalizer::getHash($url),
                'path' => parse_url($url, PHP_URL_PATH) ?: '/',
                'status_code' => $statusCode,
                'content_type' => $contentType,
                'response_time_ms' => $response->responseTimeMs,
                'title' => $title,
                'meta_description' => $metaDescription,
                'canonical_url' => $canonicalUrl,
                'robots_meta' => $robotsMeta,
                'is_indexable' => $isIndexable,
                'h1_count' => $h1Count,
                'h1_tags' => $h1Tags,
                'depth' => 0,
                'inlinks_count' => $inlinksCount,
                'outlinks_count' => $outlinksCount,
                'word_count' => $wordCount,
                'schema_types' => array_values(array_unique($schemaTypes)),
                'raw_headers' => $headers,
                'content_hash' => hash('sha256', $body),
            ],
            'findings' => $findings,
            'discovered_urls' => $discoveredUrls,
        ];
    }

    /**
     * Compute explainable SEO Health Score (0-100).
     *
     * Formula:
     * Base: 100
     * Deductions per unique page/issue:
     * - Critical: 5 points
     * - Warning: 2 points
     * - Notice: 0.5 points
     * Weighted by total crawled pages.
     */
    public static function calculateHealthScore(int $totalPages, int $criticalCount, int $warningCount, int $noticeCount): float
    {
        if ($totalPages <= 0) {
            return 100.0;
        }

        $totalPenalty = ($criticalCount * 5.0) + ($warningCount * 2.0) + ($noticeCount * 0.5);

        // Normalize penalty against total pages so larger sites aren't penalized disproportionately
        $normalizedPenalty = $totalPenalty / max(1, log($totalPages + 1, 2));

        $score = max(0.0, min(100.0, 100.0 - $normalizedPenalty));

        return round($score, 1);
    }
}
