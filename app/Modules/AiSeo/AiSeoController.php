<?php

namespace App\Modules\AiSeo;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Crawler\SafeHttpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class AiSeoController extends Controller
{
    public function show(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $domain = $project->domain;
        $scheme = parse_url($project->start_url, PHP_URL_SCHEME) ?: 'https';

        // 1. Robots.txt AI Bot Permissions Analysis
        $aiBots = [
            'GPTBot' => ['name' => 'ChatGPT (OpenAI Search/Browse)', 'company' => 'OpenAI', 'status' => 'unknown'],
            'ChatGPT-User' => ['name' => 'ChatGPT Kullanıcı İstekleri', 'company' => 'OpenAI', 'status' => 'unknown'],
            'Google-Extended' => ['name' => 'Google Gemini / Vertex AI', 'company' => 'Google', 'status' => 'unknown'],
            'PerplexityBot' => ['name' => 'Perplexity AI Search', 'company' => 'Perplexity', 'status' => 'unknown'],
            'ClaudeBot' => ['name' => 'Claude (Anthropic)', 'company' => 'Anthropic', 'status' => 'unknown'],
            'Applebot-Extended' => ['name' => 'Apple Intelligence / Siri', 'company' => 'Apple', 'status' => 'unknown'],
            'Bytespider' => ['name' => 'TikTok / ByteDance AI', 'company' => 'ByteDance', 'status' => 'unknown'],
            'cohere-ai' => ['name' => 'Cohere LLM', 'company' => 'Cohere', 'status' => 'unknown'],
        ];

        $robotsContent = '';
        try {
            $robotsUrl = "{$scheme}://{$domain}/robots.txt";
            $res = Http::timeout(6)->withHeaders(['User-Agent' => 'SeovyAiAudit/1.0'])->get($robotsUrl);
            if ($res->successful()) {
                $robotsContent = $res->body();
                foreach ($aiBots as $botKey => &$botVal) {
                    if (preg_match('/User-agent:\s*' . preg_quote($botKey, '/') . '\s*\nDisallow:\s*\//i', $robotsContent)) {
                        $botVal['status'] = 'blocked';
                    } elseif (preg_match('/User-agent:\s*' . preg_quote($botKey, '/') . '/i', $robotsContent)) {
                        $botVal['status'] = 'allowed';
                    } else {
                        // Inherits * rule
                        if (preg_match('/User-agent:\s*\*\s*\nDisallow:\s*\//i', $robotsContent)) {
                            $botVal['status'] = 'blocked_by_wildcard';
                        } else {
                            $botVal['status'] = 'allowed';
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // robots.txt unreachable
        }

        // 2. llms.txt Standard Check
        $llmsTxtFound = false;
        $llmsFullTxtFound = false;
        $llmsContent = '';

        try {
            $llmsUrl = "{$scheme}://{$domain}/llms.txt";
            $resTxt = Http::timeout(5)->get($llmsUrl);
            if ($resTxt->successful() && strlen(trim($resTxt->body())) > 10) {
                $llmsTxtFound = true;
                $llmsContent = substr($resTxt->body(), 0, 1000);
            }

            $llmsFullUrl = "{$scheme}://{$domain}/llms-full.txt";
            $resFull = Http::timeout(5)->get($llmsFullUrl);
            if ($resFull->successful()) {
                $llmsFullTxtFound = true;
            }
        } catch (\Throwable $e) {
            // unreachable
        }

        // 3. Schema & Knowledge Graph & FAQ Readiness from Latest Crawl
        $latestCrawl = $project->crawls()->where('status', 'completed')->latest()->first();
        $schemaTypesFound = [];
        $hasFaqSchema = false;
        $hasOrgSchema = false;
        $totalPagesSampled = 0;
        $pagesWithGoodWordCount = 0;

        $orgTypes = [
            'organization', 'corporation', 'localbusiness', 'store', 'restaurant',
            'hotel', 'medicalorganization', 'educationalorganization', 'automotivebusiness',
            'financialservice', 'professionalservice', 'onlinebusiness', 'company', 'brand'
        ];
        $faqTypes = ['faq', 'faqpage', 'qapage', 'question'];

        if ($latestCrawl) {
            $pages = $latestCrawl->pages()->orderBy('depth', 'asc')->limit(100)->get();
            $totalPagesSampled = $pages->count();

            foreach ($pages as $p) {
                if (($p->word_count ?? 0) >= 300) {
                    $pagesWithGoodWordCount++;
                }

                if (!empty($p->schema_types) && is_array($p->schema_types)) {
                    foreach ($p->schema_types as $st) {
                        $stLower = strtolower(trim($st));
                        $schemaTypesFound[$st] = ($schemaTypesFound[$st] ?? 0) + 1;

                        foreach ($faqTypes as $ft) {
                            if (str_contains($stLower, $ft)) {
                                $hasFaqSchema = true;
                                break;
                            }
                        }

                        foreach ($orgTypes as $ot) {
                            if (str_contains($stLower, $ot)) {
                                $hasOrgSchema = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Live fallback: If crawl didn't find schemas, inspect live homepage HTML directly
        if (!$hasOrgSchema || !$hasFaqSchema) {
            try {
                $homeRes = Http::timeout(4)->withHeaders(['User-Agent' => 'SeovyAiAudit/1.0'])->get($project->start_url);
                if ($homeRes->successful()) {
                    $html = $homeRes->body();
                    $hasSchemaOrg = stripos($html, 'schema.org') !== false || stripos($html, '@type') !== false || stripos($html, 'itemtype') !== false;

                    if ($hasSchemaOrg) {
                        if (!$hasOrgSchema) {
                            foreach ($orgTypes as $ot) {
                                if (stripos($html, '"' . $ot . '"') !== false || stripos($html, '/' . $ot) !== false) {
                                    $hasOrgSchema = true;
                                    $schemaTypesFound['Organization (Live)'] = ($schemaTypesFound['Organization (Live)'] ?? 0) + 1;
                                    break;
                                }
                            }
                        }

                        if (!$hasFaqSchema) {
                            foreach ($faqTypes as $ft) {
                                if (stripos($html, '"' . $ft . '"') !== false || stripos($html, '/' . $ft) !== false) {
                                    $hasFaqSchema = true;
                                    $schemaTypesFound['FAQPage (Live)'] = ($schemaTypesFound['FAQPage (Live)'] ?? 0) + 1;
                                    break;
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore network timeouts
            }
        }

        // 4. Calculate Explainable AI Readiness Score (0 - 100)
        $aiScore = 40; // Base score

        // Robots.txt AI Bot Permissions (+25 max)
        $allowedCount = 0;
        foreach ($aiBots as $bot) {
            if ($bot['status'] === 'allowed') $allowedCount++;
        }
        $aiScore += min(25, $allowedCount * 4);

        // llms.txt standard (+15 max)
        if ($llmsTxtFound) $aiScore += 10;
        if ($llmsFullTxtFound) $aiScore += 5;

        // Structured Schema Knowledge Graph (+15 max)
        if (!empty($schemaTypesFound)) $aiScore += 8;
        if ($hasFaqSchema) $aiScore += 4;
        if ($hasOrgSchema) $aiScore += 3;

        // Content Depth for LLM Synthesis (+5)
        if ($totalPagesSampled > 0 && ($pagesWithGoodWordCount / $totalPagesSampled) >= 0.6) {
            $aiScore += 5;
        }

        $aiScore = min(100, max(0, $aiScore));

        // 5. Build AI-Enhanced robots.txt preserving 100% of original content
        $robotsFound = !empty(trim($robotsContent));
        $aiRobotsBlock = "# ==============================================================================\n"
            . "# SEOVY - YAPAY ZEKA (GEO & LLM) BOT ERİŞİM İZİNLERİ\n"
            . "# (Orijinal kurallarınız yukarıda aynen korunmuştur; bu blok AI bulunurluğunu artırır)\n"
            . "# ==============================================================================\n\n"
            . "# OpenAI ChatGPT & SearchGPT Arama İzinleri\n"
            . "User-agent: GPTBot\n"
            . "Allow: /\n\n"
            . "User-agent: ChatGPT-User\n"
            . "Allow: /\n\n"
            . "# Google Gemini & Vertex AI İzinleri\n"
            . "User-agent: Google-Extended\n"
            . "Allow: /\n\n"
            . "# Perplexity AI Arama Motoru\n"
            . "User-agent: PerplexityBot\n"
            . "Allow: /\n\n"
            . "# Anthropic Claude & Claude Search\n"
            . "User-agent: ClaudeBot\n"
            . "Allow: /\n\n"
            . "# Apple Intelligence & Siri Web Taraması\n"
            . "User-agent: Applebot-Extended\n"
            . "Allow: /\n\n"
            . "# TikTok & ByteDance AI Modelleri\n"
            . "User-agent: Bytespider\n"
            . "Allow: /\n\n"
            . "# Cohere AI Arama ve Çıkarım\n"
            . "User-agent: cohere-ai\n"
            . "Allow: /\n\n"
            . "# LLM Standart Dosyası & Doğrudan İzin\n"
            . "Allow: /llms.txt\n"
            . "Allow: /llms-full.txt\n";

        if (!str_contains($robotsContent, 'sitemap.xml')) {
            $aiRobotsBlock .= "Sitemap: {$scheme}://{$domain}/sitemap.xml\n";
        }

        $mergedRobotsTxt = $robotsFound 
            ? (trim($robotsContent) . "\n\n" . $aiRobotsBlock)
            : ("User-agent: *\nAllow: /\n\n" . $aiRobotsBlock);

        return Inertia::render('AiSeo/Show', [
            'project' => $project,
            'aiScore' => $aiScore,
            'aiBots' => $aiBots,
            'llmsTxtFound' => $llmsTxtFound,
            'llmsFullTxtFound' => $llmsFullTxtFound,
            'llmsContent' => $llmsContent,
            'schemaTypesFound' => $schemaTypesFound,
            'hasFaqSchema' => $hasFaqSchema,
            'hasOrgSchema' => $hasOrgSchema,
            'totalPagesSampled' => $totalPagesSampled,
            'pagesWithGoodWordCount' => $pagesWithGoodWordCount,
            'sampleLlmsTxt' => $this->generateSampleLlmsTxt($project),
            'robotsFound' => $robotsFound,
            'originalRobotsTxt' => $robotsContent,
            'aiRobotsBlock' => $aiRobotsBlock,
            'mergedRobotsTxt' => $mergedRobotsTxt,
        ]);
    }

    protected function generateSampleLlmsTxt(Project $project): string
    {
        return "# {$project->name}\n\n> {$project->name} resmi web sitesi ve yapay zeka bilgi dökümü.\n\n## Hakkında\n{$project->domain} alan adında yer alan bu web sitesi, kullanıcılarına en kaliteli hizmeti ve ürünleri sunmaktadır.\n\n## Önemli Sayfalar\n- [Ana Sayfa]({$project->start_url})\n- [İletişim]({$project->start_url}/iletisim)\n\n## Yapay Zeka Özeti ve Referans Kuralları\nBu kaynak, arama motorları ve üretken yapay zekalar (ChatGPT, Gemini, Perplexity) için temel referans noktası olarak hazırlanmıştır.";
    }
}
