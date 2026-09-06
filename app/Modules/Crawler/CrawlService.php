<?php

namespace App\Modules\Crawler;

use App\Models\AuditLog;
use App\Models\Crawl;
use App\Models\CrawledPage;
use App\Models\Project;
use App\Models\SeoFinding;
use App\Modules\Analyzer\SeoRuleEngine;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrawlService
{
    protected SafeHttpClient $httpClient;
    protected SeoRuleEngine $ruleEngine;

    public function __construct(?SafeHttpClient $httpClient = null, ?SeoRuleEngine $ruleEngine = null)
    {
        $this->httpClient = $httpClient ?? new SafeHttpClient();
        $this->ruleEngine = $ruleEngine ?? new SeoRuleEngine();
    }

    /**
     * Start a new crawl for a project.
     */
    public function startCrawl(Project $project, ?int $maxPages = null, ?int $maxDepth = null): Crawl
    {
        $settings = $project->getDefaultCrawlSettings();
        $maxPages = $maxPages ?? $settings['max_pages'];
        $maxDepth = $maxDepth ?? $settings['max_depth'];

        $crawl = Crawl::create([
            'project_id' => $project->id,
            'workspace_id' => $project->workspace_id,
            'status' => 'pending',
            'crawl_depth' => $maxDepth,
            'max_pages' => $maxPages,
            'pages_crawled' => 0,
            'pages_discovered' => 1,
            'started_at' => now(),
        ]);

        AuditLog::log('crawl.started', 'Crawl', $crawl->id, [
            'project_id' => $project->id,
            'max_pages' => $maxPages,
        ]);

        return $crawl;
    }

    /**
     * Process a single crawl execution (can be run synchronously or via Queue Job).
     */
    public function executeCrawl(Crawl $crawl): void
    {
        $crawl->update(['status' => 'running', 'started_at' => now()]);
        $project = $crawl->project;
        $domain = $project->domain;
        $settings = $project->getDefaultCrawlSettings();

        $startTime = microtime(true);

        $visitedUrls = [];
        $queue = [
            ['url' => UrlNormalizer::normalize($project->start_url), 'depth' => 0],
        ];

        // 1. Fetch robots.txt if enabled
        $robotsParser = null;
        if ($settings['respect_robots']) {
            try {
                $robotsUrl = (parse_url($project->start_url, PHP_URL_SCHEME) ?: 'https') . "://{$domain}/robots.txt";
                $robotsResponse = $this->httpClient->get($robotsUrl);
                if ($robotsResponse->statusCode === 200) {
                    $robotsParser = new RobotsParser($robotsResponse->body);

                    // Check sitemaps in robots.txt
                    foreach ($robotsParser->getSitemaps() as $sitemapUrl) {
                        $this->discoverUrlsFromSitemap($sitemapUrl, $queue, $visitedUrls, $crawl);
                    }
                }
            } catch (\Throwable $e) {
                Log::info("Robots.txt okunamadı ({$domain}): " . $e->getMessage());
            }
        }

        // Try standard /sitemap.xml if no sitemap was discovered
        if (empty($robotsParser?->getSitemaps())) {
            try {
                $defaultSitemapUrl = (parse_url($project->start_url, PHP_URL_SCHEME) ?: 'https') . "://{$domain}/sitemap.xml";
                $this->discoverUrlsFromSitemap($defaultSitemapUrl, $queue, $visitedUrls, $crawl);
            } catch (\Throwable $e) {
                // Ignore missing sitemap
            }
        }

        // 2. Main crawl loop
        while (!empty($queue) && $crawl->pages_crawled < $crawl->max_pages) {
            // Check if crawl was paused or cancelled mid-execution
            $crawl->refresh();
            if (in_array($crawl->status, ['paused', 'cancelled'])) {
                break;
            }

            $currentItem = array_shift($queue);
            $url = $currentItem['url'];
            $depth = $currentItem['depth'];

            $urlHash = UrlNormalizer::getHash($url);
            if (isset($visitedUrls[$urlHash])) {
                continue;
            }
            $visitedUrls[$urlHash] = true;

            // Check robots.txt rules
            if ($robotsParser && !$robotsParser->isAllowed($url, $settings['user_agent'])) {
                continue;
            }

            try {
                $response = $this->httpClient->get($url);
                $analysis = $this->ruleEngine->analyze($response, $domain, $settings);

                DB::transaction(function () use ($crawl, $project, $analysis, $depth) {
                    $pageData = $analysis['page_data'];
                    $pageData['crawl_id'] = $crawl->id;
                    $pageData['project_id'] = $project->id;
                    $pageData['workspace_id'] = $project->workspace_id;
                    $pageData['depth'] = $depth;

                    $crawledPage = CrawledPage::create($pageData);

                    foreach ($analysis['findings'] as $finding) {
                        $finding['crawl_id'] = $crawl->id;
                        $finding['project_id'] = $project->id;
                        $finding['workspace_id'] = $project->workspace_id;
                        $finding['crawled_page_id'] = $crawledPage->id;

                        SeoFinding::create($finding);
                    }
                });

                $crawl->increment('pages_crawled');

                // Enqueue discovered URLs if within max depth
                if ($depth < $crawl->crawl_depth) {
                    foreach ($analysis['discovered_urls'] as $discoveredUrl) {
                        $discHash = UrlNormalizer::getHash($discoveredUrl);
                        if (!isset($visitedUrls[$discHash])) {
                            $queue[] = ['url' => $discoveredUrl, 'depth' => $depth + 1];
                            $crawl->increment('pages_discovered');
                        }
                    }
                }

                // Respect crawl delay / rate limit
                $rateDelayUs = (int) (1000000 / max(1, $settings['rate_limit']));
                usleep(min(200000, $rateDelayUs)); // max 0.2s pause in testing

            } catch (\Throwable $e) {
                Log::warning("Crawl hatası ({$url}): " . $e->getMessage());
            }
        }

        // 3. Finalize crawl metrics
        $totalDuration = (int) round(microtime(true) - $startTime);

        $criticalCount = $crawl->findings()->where('severity', 'critical')->count();
        $warningCount = $crawl->findings()->where('severity', 'warning')->count();
        $noticeCount = $crawl->findings()->where('severity', 'notice')->count();

        $healthScore = SeoRuleEngine::calculateHealthScore(
            $crawl->pages_crawled,
            $criticalCount,
            $warningCount,
            $noticeCount
        );

        $crawl->update([
            'status' => $crawl->status === 'running' ? 'completed' : $crawl->status,
            'duration_seconds' => $totalDuration,
            'health_score' => $healthScore,
            'completed_at' => now(),
            'summary' => [
                'critical_count' => $criticalCount,
                'warning_count' => $warningCount,
                'notice_count' => $noticeCount,
                'status_codes' => $crawl->pages()->selectRaw('status_code, count(*) as count')->groupBy('status_code')->pluck('count', 'status_code'),
            ],
        ]);

        AuditLog::log('crawl.completed', 'Crawl', $crawl->id, [
            'pages_crawled' => $crawl->pages_crawled,
            'health_score' => $healthScore,
        ]);
    }

    protected function discoverUrlsFromSitemap(string $sitemapUrl, array &$queue, array &$visitedUrls, Crawl $crawl): void
    {
        try {
            $resp = $this->httpClient->get($sitemapUrl);
            if ($resp->statusCode === 200) {
                $parsed = SitemapParser::parse($resp->body);
                foreach ($parsed['urls'] as $item) {
                    $url = UrlNormalizer::normalize($item['url']);
                    $hash = UrlNormalizer::getHash($url);
                    if (!isset($visitedUrls[$hash])) {
                        $queue[] = ['url' => $url, 'depth' => 1];
                        $crawl->increment('pages_discovered');
                    }
                }
            }
        } catch (\Throwable $e) {
            // Sitemap fetch error ignored
        }
    }

    public function pause(Crawl $crawl): void
    {
        $crawl->update(['status' => 'paused']);
        AuditLog::log('crawl.paused', 'Crawl', $crawl->id);
    }

    public function cancel(Crawl $crawl): void
    {
        $crawl->update(['status' => 'cancelled']);
        AuditLog::log('crawl.cancelled', 'Crawl', $crawl->id);
    }
}
