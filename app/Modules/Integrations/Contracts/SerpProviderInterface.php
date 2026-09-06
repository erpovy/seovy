<?php

namespace App\Modules\Integrations\Contracts;

interface SerpProviderInterface
{
    /**
     * Fetch keyword rankings and search volumes for target domain.
     *
     * @param string $domain
     * @param array<string> $keywords
     * @param string $country
     * @param string $language
     * @return array<string, array{position: ?int, search_volume: ?int, url: ?string}>
     */
    public function checkRankings(string $domain, array $keywords, string $country = 'TR', string $language = 'tr'): array;

    /**
     * Check if the provider is configured and available.
     */
    public function isConfigured(): bool;
}
