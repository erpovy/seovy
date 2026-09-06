<?php

namespace App\Modules\Integrations\Providers;

use App\Modules\Integrations\Contracts\SerpProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerpApiProvider implements SerpProviderInterface
{
    protected ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.serpapi.key', env('SERPAPI_KEY'));
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function checkRankings(string $domain, array $keywords, string $country = 'TR', string $language = 'tr'): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $cleanDomain = strtolower(preg_replace('/^https?:\/\//i', '', $domain));
        $cleanDomain = preg_replace('/^www\./i', '', $cleanDomain);
        $cleanDomain = explode('/', $cleanDomain)[0];

        $results = [];

        foreach ($keywords as $kw) {
            $kwText = trim($kw);
            if (empty($kwText)) continue;

            try {
                $response = Http::timeout(20)->get('https://serpapi.com/search.json', [
                    'engine' => 'google',
                    'q' => $kwText,
                    'gl' => strtolower($country),
                    'hl' => strtolower($language),
                    'api_key' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $organic = $data['organic_results'] ?? [];
                    $matchedPos = null;
                    $matchedUrl = null;

                    foreach ($organic as $item) {
                        $link = $item['link'] ?? '';
                        $displayedLink = $item['displayed_link'] ?? '';
                        if (str_contains(strtolower($link), $cleanDomain) || str_contains(strtolower($displayedLink), $cleanDomain)) {
                            $matchedPos = $item['position'] ?? null;
                            $matchedUrl = $link;
                            break;
                        }
                    }

                    $results[$kwText] = [
                        'position' => $matchedPos,
                        'search_volume' => null,
                        'url' => $matchedUrl,
                    ];
                } else {
                    $results[$kwText] = [
                        'position' => null,
                        'search_volume' => null,
                        'url' => null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::error("SerpApi Provider error: " . $e->getMessage());
                $results[$kwText] = [
                    'position' => null,
                    'search_volume' => null,
                    'url' => null,
                ];
            }
        }

        return $results;
    }
}
