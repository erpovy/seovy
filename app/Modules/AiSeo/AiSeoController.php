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
    public function show(Request , Project )
    {
        Gate::authorize('view', );

         = ->domain;
         = parse_url(->start_url, PHP_URL_SCHEME) ?: 'https';

        // 1. Robots.txt AI Bot Permissions Analysis
         = [
            'GPTBot' => ['name' => 'ChatGPT (OpenAI Search/Browse)', 'company' => 'OpenAI', 'status' => 'unknown'],
            'ChatGPT-User' => ['name' => 'ChatGPT Kullanıcı İstekleri', 'company' => 'OpenAI', 'status' => 'unknown'],
            'Google-Extended' => ['name' => 'Google Gemini / Vertex AI', 'company' => 'Google', 'status' => 'unknown'],
            'PerplexityBot' => ['name' => 'Perplexity AI Search', 'company' => 'Perplexity', 'status' => 'unknown'],
            'ClaudeBot' => ['name' => 'Claude (Anthropic)', 'company' => 'Anthropic', 'status' => 'unknown'],
            'Applebot-Extended' => ['name' => 'Apple Intelligence / Siri', 'company' => 'Apple', 'status' => 'unknown'],
            'Bytespider' => ['name' => 'TikTok / ByteDance AI', 'company' => 'ByteDance', 'status' => 'unknown'],
            'cohere-ai' => ['name' => 'Cohere LLM', 'company' => 'Cohere', 'status' => 'unknown'],
        ];

         = '';
        try {
             = "{}://{}/robots.txt";
             = Http::timeout(6)->withHeaders(['User-Agent' => 'SeovyAiAudit/1.0'])->get();
            if (->successful()) {
                 = ->body();
                foreach ( as  => &) {
                    if (preg_match('/User-agent:\s*' . preg_quote(, '/') . '\s*\nDisallow:\s*\//i', )) {
                        ['status'] = 'blocked';
                    } elseif (preg_match('/User-agent:\s*' . preg_quote(, '/') . '/i', )) {
                        ['status'] = 'allowed';
                    } else {
                        // Inherits * rule
                        if (preg_match('/User-agent:\s*\*\s*\nDisallow:\s*\//i', )) {
                            ['status'] = 'blocked_by_wildcard';
                        } else {
                            ['status'] = 'allowed';
                        }
                    }
                }
            }
        } catch (\Throwable ) {
            // robots.txt unreachable
        }

        // 2. llms.txt Standard Check
         = false;
         = false;
         = '';

        try {
             = "{}://{}/llms.txt";
             = Http::timeout(5)->get();
            if (->successful() && strlen(trim(->body())) > 10) {
                 = true;
                 = substr(->body(), 0, 1000);
            }

             = "{}://{}/llms-full.txt";
             = Http::timeout(5)->get();
            if (->successful()) {
                 = true;
            }
        } catch (\Throwable ) {
            // unreachable
        }

        // 3. Schema & Knowledge Graph & FAQ Readiness from Latest Crawl
         = ->crawls()->where('status', 'completed')->latest()->first();
         = [];
         = false;
         = false;
         = 0;
         = 0;

        if () {
             = ->pages()->limit(50)->get();
             = ->count();

            foreach ( as ) {
                if (->word_count >= 300) {
                    ++;
                }

                if (!empty(->schema_types) && is_array(->schema_types)) {
                    foreach (->schema_types as ) {
                        [] = ([] ?? 0) + 1;
                        if (str_contains(strtolower(), 'faq'))  = true;
                        if (str_contains(strtolower(), 'organization'))  = true;
                    }
                }
            }
        }

        // 4. Calculate Explainable AI Readiness Score (0 - 100)
         = 40; // Base score

        // Robots.txt AI Bot Permissions (+25 max)
         = 0;
        foreach ( as ) {
            if (['status'] === 'allowed') ++;
        }
         += min(25,  * 4);

        // llms.txt standard (+15 max)
        if ()  += 10;
        if ()  += 5;

        // Structured Schema Knowledge Graph (+15 max)
        if (!empty())  += 8;
        if ()  += 4;
        if ()  += 3;

        // Content Depth for LLM Synthesis (+5)
        if ( > 0 && ( / ) >= 0.6) {
             += 5;
        }

         = min(100, max(0, ));

        return Inertia::render('AiSeo/Show', [
            'project' => ,
            'aiScore' => ,
            'aiBots' => ,
            'llmsTxtFound' => ,
            'llmsFullTxtFound' => ,
            'llmsContent' => ,
            'schemaTypesFound' => ,
            'hasFaqSchema' => ,
            'hasOrgSchema' => ,
            'totalPagesSampled' => ,
            'pagesWithGoodWordCount' => ,
            'sampleLlmsTxt' => ->generateSampleLlmsTxt(),
        ]);
    }

    protected function generateSampleLlmsTxt(Project ): string
    {
        return "# {->name}\n\n> {->name} resmi web sitesi ve yapay zeka bilgi dökümü.\n\n## Hakkında\n{->domain} alan adında yer alan bu web sitesi, kullanıcılarına en kaliteli hizmeti ve ürünleri sunmaktadır.\n\n## Önemli Sayfalar\n- [Ana Sayfa]({->start_url})\n- [İletişim]({->start_url}/iletisim)\n\n## Yapay Zeka Özeti ve Referans Kuralları\nBu kaynak, arama motorları ve üretken yapay zekalar (ChatGPT, Gemini, Perplexity) için temel referans noktası olarak hazırlanmıştır.";
    }
}
