<?php

namespace App\Modules\Crawler;

class UrlNormalizer
{
    protected static array $trackingParams = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'fbclid', 'gclid', 'msclkid', 'mc_cid', 'mc_eid', '_ga', '_gl', 'ref',
    ];

    /**
     * Normalize a URL: strip fragments, sort query params, strip marketing trackers.
     */
    public static function normalize(string $url): string
    {
        $parts = parse_url(trim($url));
        if (!$parts || !isset($parts['scheme']) || !isset($parts['host'])) {
            return $url;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        // Standardize default ports
        if (($scheme === 'http' && $port === ':80') || ($scheme === 'https' && $port === ':443')) {
            $port = '';
        }

        $path = $parts['path'] ?? '/';
        // Remove repeated slashes
        $path = preg_replace('#/{2,}#', '/', $path);

        $query = '';
        if (isset($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            // Remove tracking parameters
            foreach (static::$trackingParams as $tracker) {
                unset($queryParams[$tracker]);
            }
            if (!empty($queryParams)) {
                ksort($queryParams);
                $query = '?' . http_build_query($queryParams);
            }
        }

        return "{$scheme}://{$host}{$port}{$path}{$query}";
    }

    /**
     * Resolve a relative URL against a base URL.
     */
    public static function resolveRelativeUrl(string $baseUrl, string $relativeUrl): string
    {
        $relativeUrl = trim($relativeUrl);

        // Discard javascript:, mailto:, tel:
        if (preg_match('/^(javascript:|mailto:|tel:|data:|#)/i', $relativeUrl)) {
            return '';
        }

        // Already absolute URL
        if (preg_match('/^https?:\/\//i', $relativeUrl)) {
            return static::normalize($relativeUrl);
        }

        // Protocol-relative URL (//example.com/foo)
        if (str_starts_with($relativeUrl, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return static::normalize("{$scheme}:{$relativeUrl}");
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';
        $port = isset($base['port']) ? ':' . $base['port'] : '';
        $basePath = $base['path'] ?? '/';

        // Root-relative (/path/to/page)
        if (str_starts_with($relativeUrl, '/')) {
            return static::normalize("{$scheme}://{$host}{$port}{$relativeUrl}");
        }

        // Query only (?foo=bar)
        if (str_starts_with($relativeUrl, '?')) {
            return static::normalize("{$scheme}://{$host}{$port}{$basePath}{$relativeUrl}");
        }

        // Relative path (foo/bar or ../foo)
        $dir = dirname($basePath);
        if ($dir === '\\' || $dir === '.') {
            $dir = '';
        }

        $fullPath = ($dir ? '/' . trim($dir, '/') : '') . '/' . $relativeUrl;
        // Resolve ../ and ./
        $segments = explode('/', $fullPath);
        $resolvedSegments = [];
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($resolvedSegments);
            } else {
                $resolvedSegments[] = $segment;
            }
        }

        $path = '/' . implode('/', $resolvedSegments);
        return static::normalize("{$scheme}://{$host}{$port}{$path}");
    }

    /**
     * Check if a URL belongs to the target project domain.
     */
    public static function isInternal(string $url, string $domain, bool $includeSubdomains = false): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        $host = strtolower($host);
        $domain = strtolower($domain);

        // Strip www. for comparison
        $cleanHost = preg_replace('/^www\./', '', $host);
        $cleanDomain = preg_replace('/^www\./', '', $domain);

        if ($cleanHost === $cleanDomain) {
            return true;
        }

        if ($includeSubdomains && str_ends_with($cleanHost, '.' . $cleanDomain)) {
            return true;
        }

        return false;
    }

    public static function getHash(string $url): string
    {
        return hash('sha256', static::normalize($url));
    }
}
