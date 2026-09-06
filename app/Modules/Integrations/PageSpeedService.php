<?php

namespace App\Modules\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PageSpeedService
{
    protected ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.pagespeed.key', env('PAGESPEED_API_KEY'));
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Run PageSpeed audit for mobile and desktop strategies.
     *
     * @return array{mobile: ?array, desktop: ?array, error: ?string}
     */
    public function audit(string $url): array
    {
        return [
            'mobile' => $this->auditStrategy($url, 'mobile'),
            'desktop' => $this->auditStrategy($url, 'desktop'),
            'error' => null,
        ];
    }

    protected function auditStrategy(string $url, string $strategy = 'mobile'): ?array
    {
        $params = [
            'url' => $url,
            'strategy' => $strategy,
            'category' => ['PERFORMANCE', 'ACCESSIBILITY', 'BEST_PRACTICES', 'SEO'],
        ];

        if (!empty($this->apiKey)) {
            $params['key'] = $this->apiKey;
        }

        try {
            $response = Http::timeout(45)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', $params);

            if (!$response->successful()) {
                Log::warning("PageSpeed API ({$strategy}) başarısız: " . $response->body());
                return null;
            }

            $data = $response->json();
            $lighthouse = $data['lighthouseResult'] ?? [];
            $categories = $lighthouse['categories'] ?? [];
            $audits = $lighthouse['audits'] ?? [];
            $loadingExperience = $data['loadingExperience'] ?? []; // CrUX Field Data

            // Lab Metrics (Lighthouse)
            $labMetrics = [
                'fcp' => $audits['first-contentful-paint']['displayValue'] ?? null,
                'lcp' => $audits['largest-contentful-paint']['displayValue'] ?? null,
                'tbt' => $audits['total-blocking-time']['displayValue'] ?? null,
                'cls' => $audits['cumulative-layout-shift']['displayValue'] ?? null,
                'speed_index' => $audits['speed-index']['displayValue'] ?? null,
            ];

            // Field Metrics (CrUX Real User Experience)
            $fieldMetrics = null;
            if (!empty($loadingExperience['metrics'])) {
                $fieldMetrics = [
                    'overall_category' => $loadingExperience['overall_category'] ?? null,
                    'lcp_p75' => $loadingExperience['metrics']['LARGEST_CONTENTFUL_PAINT_MS']['percentile'] ?? null,
                    'cls_p75' => $loadingExperience['metrics']['CUMULATIVE_LAYOUT_SHIFT_SCORE']['percentile'] ?? null,
                    'inp_p75' => $loadingExperience['metrics']['INTERACTION_TO_NEXT_PAINT']['percentile'] ?? null,
                ];
            }

            return [
                'strategy' => $strategy,
                'scores' => [
                    'performance' => isset($categories['performance']['score']) ? (int) round($categories['performance']['score'] * 100) : null,
                    'accessibility' => isset($categories['accessibility']['score']) ? (int) round($categories['accessibility']['score'] * 100) : null,
                    'best_practices' => isset($categories['best-practices']['score']) ? (int) round($categories['best-practices']['score'] * 100) : null,
                    'seo' => isset($categories['seo']['score']) ? (int) round($categories['seo']['score'] * 100) : null,
                ],
                'lab_metrics' => $labMetrics,
                'field_metrics' => $fieldMetrics, // Distinguish clearly between Lab & CrUX
                'audited_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::error("PageSpeed audit istisnası: " . $e->getMessage());
            return null;
        }
    }
}
