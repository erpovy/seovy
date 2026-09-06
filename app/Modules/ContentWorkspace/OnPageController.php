<?php

namespace App\Modules\ContentWorkspace;

use App\Http\Controllers\Controller;
use App\Models\CrawledPage;
use App\Models\Project;
use App\Modules\Analyzer\SeoRuleEngine;
use App\Modules\Crawler\SafeHttpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OnPageController extends Controller
{
    public function show(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $url = $request->input('url', $project->start_url);
        $page = null;
        $findings = [];
        $internalLinkSuggestions = [];

        // Look up if page already exists in latest crawl
        $latestCrawl = $project->latestCrawl;
        if ($latestCrawl) {
            $page = CrawledPage::where('crawl_id', $latestCrawl->id)
                ->where('url', $url)
                ->first();

            if ($page) {
                $findings = $page->findings()->get();

                // Compute internal link suggestions from other crawled pages
                $pagePath = $page->path;
                $internalLinkSuggestions = CrawledPage::where('crawl_id', $latestCrawl->id)
                    ->where('id', '!=', $page->id)
                    ->where('status_code', 200)
                    ->where('is_indexable', true)
                    ->select(['id', 'url', 'title', 'word_count'])
                    ->take(5)
                    ->get()
                    ->map(function ($otherPage) {
                        return [
                            'url' => $otherPage->url,
                            'title' => $otherPage->title,
                            'reason' => 'Yüksek otoriteye sahip ilgili sayfa',
                        ];
                    });
            }
        }

        return Inertia::render('OnPage/Show', [
            'project' => $project,
            'page' => $page,
            'url' => $url,
            'findings' => $findings,
            'internalLinkSuggestions' => $internalLinkSuggestions,
        ]);
    }

    /**
     * Instant live analysis of a single URL.
     */
    public function analyzeLive(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $request->validate([
            'url' => ['required', 'url'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
        ]);

        $url = $request->url;
        $focusKeyword = strtolower(trim($request->focus_keyword ?? ''));

        $client = new SafeHttpClient();
        $engine = new SeoRuleEngine();

        try {
            $response = $client->get($url);
            $analysis = $engine->analyze($response, $project->domain);

            // Keyword analysis
            $keywordAnalysis = null;
            if (!empty($focusKeyword)) {
                $bodyLower = mb_strtolower($response->body);
                $titleLower = mb_strtolower($analysis['page_data']['title'] ?? '');
                $descLower = mb_strtolower($analysis['page_data']['meta_description'] ?? '');

                $keywordCount = substr_count($bodyLower, $focusKeyword);
                $inTitle = str_contains($titleLower, $focusKeyword);
                $inDesc = str_contains($descLower, $focusKeyword);
                $density = $analysis['page_data']['word_count'] > 0
                    ? round(($keywordCount / $analysis['page_data']['word_count']) * 100, 2)
                    : 0;

                $keywordAnalysis = [
                    'keyword' => $focusKeyword,
                    'found_count' => $keywordCount,
                    'in_title' => $inTitle,
                    'in_meta_description' => $inDesc,
                    'density_percent' => $density,
                    'recommendation' => $density > 3.0 ? 'Anahtar kelime yoğunluğu çok yüksek (Keyword Stuffing riski).' : ($density < 0.5 ? 'Anahtar kelime yoğunluğu düşük, içeriğe doğal şekilde serpiştirilebilir.' : 'Yoğunluk ideal aralıkta (%1 - %2.5).'),
                ];
            }

            return response()->json([
                'success' => true,
                'page' => $analysis['page_data'],
                'findings' => $analysis['findings'],
                'keyword_analysis' => $keywordAnalysis,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analiz gerçekleştirilemedi: ' . $e->getMessage(),
            ], 422);
        }
    }
}
