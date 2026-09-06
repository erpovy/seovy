<?php

namespace App\Modules\Integrations\Providers;

use App\Modules\Integrations\Contracts\SerpProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataForSeoProvider implements SerpProviderInterface
{
    protected ?string $login;
    protected ?string $password;

    public function __construct(?string $login = null, ?string $password = null)
    {
        $this->login = $login ?? config('services.dataforseo.login');
        $this->password = $password ?? config('services.dataforseo.password');
    }

    public function isConfigured(): bool
    {
        return !empty($this->login) && !empty($this->password);
    }

    public function checkRankings(string $domain, array $keywords, string $country = 'TR', string $language = 'tr'): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $results = [];

        try {
            $postData = [];
            foreach ($keywords as $kw) {
                $postData[] = [
                    'language_code' => strtolower($language),
                    'location_code' => $country === 'TR' ? 2792 : 2840, // Turkey vs US
                    'keyword' => mb_convert_encoding($kw, 'UTF-8'),
                    'target' => $domain,
                ];
            }

            $response = Http::withBasicAuth($this->login, $this->password)
                ->timeout(30)
                ->post('https://api.dataforseo.com/v3/serp/google/organic/live/advanced', $postData);

            if ($response->successful()) {
                $tasks = $response->json('tasks') ?? [];
                foreach ($tasks as $task) {
                    $kw = $task['data']['keyword'] ?? null;
                    if (!$kw) continue;

                    $items = $task['result'][0]['items'] ?? [];
                    $matchedPos = null;
                    $matchedUrl = null;

                    foreach ($items as $item) {
                        if (isset($item['domain']) && str_contains(strtolower($item['domain']), strtolower($domain))) {
                            $matchedPos = $item['rank_group'] ?? $item['rank_absolute'] ?? null;
                            $matchedUrl = $item['url'] ?? null;
                            break;
                        }
                    }

                    $results[$kw] = [
                        'position' => $matchedPos,
                        'search_volume' => null,
                        'url' => $matchedUrl,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error("DataForSEO API hatası: " . $e->getMessage());
        }

        return $results;
    }
}
