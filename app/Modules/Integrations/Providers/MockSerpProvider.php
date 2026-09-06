<?php

namespace App\Modules\Integrations\Providers;

use App\Modules\Integrations\Contracts\SerpProviderInterface;

class MockSerpProvider implements SerpProviderInterface
{
    public function isConfigured(): bool
    {
        return app()->environment('local', 'testing');
    }

    public function checkRankings(string $domain, array $keywords, string $country = 'TR', string $language = 'tr'): array
    {
        // Explicitly labeled mock data for local testing environments
        $results = [];
        $dummyPositions = [3, 7, 12, 1, 19, 4, 8, 15];

        foreach ($keywords as $idx => $kw) {
            $results[$kw] = [
                'position' => $dummyPositions[$idx % count($dummyPositions)],
                'search_volume' => 1200,
                'url' => "https://{$domain}/test-page-" . ($idx + 1),
            ];
        }

        return $results;
    }
}
