<?php

namespace App\Modules\Crawler;

use SimpleXMLElement;

class SitemapParser
{
    /**
     * Parse sitemap XML content and return discovered URLs and sub-sitemaps.
     */
    public static function parse(string $xmlContent): array
    {
        $urls = [];
        $subSitemaps = [];

        // Suppress libxml errors and parse
        $previousEntityLoader = libxml_disable_entity_loader(true);
        libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($xmlContent, SimpleXMLElement::class, LIBXML_NOCDATA);

            if ($xml !== false) {
                // Check if it's a sitemap index (<sitemapindex>)
                if ($xml->getName() === 'sitemapindex') {
                    foreach ($xml->sitemap as $sitemap) {
                        if (isset($sitemap->loc)) {
                            $loc = trim((string) $sitemap->loc);
                            if (!empty($loc)) {
                                $subSitemaps[] = $loc;
                            }
                        }
                    }
                }
                // Check if it's a standard sitemap (<urlset>)
                elseif ($xml->getName() === 'urlset') {
                    foreach ($xml->url as $entry) {
                        if (isset($entry->loc)) {
                            $loc = trim((string) $entry->loc);
                            if (!empty($loc)) {
                                $urls[] = [
                                    'url' => $loc,
                                    'lastmod' => isset($entry->lastmod) ? (string) $entry->lastmod : null,
                                    'changefreq' => isset($entry->changefreq) ? (string) $entry->changefreq : null,
                                    'priority' => isset($entry->priority) ? (float) $entry->priority : null,
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Malformed XML handling
        }

        libxml_clear_errors();
        libxml_disable_entity_loader($previousEntityLoader);

        return [
            'urls' => $urls,
            'sub_sitemaps' => $subSitemaps,
        ];
    }
}
