<?php

namespace App\Modules\Crawler;

class RobotsParser
{
    protected array $rules = [];
    protected array $sitemaps = [];
    protected ?int $crawlDelay = null;

    public function __construct(string $robotsTxtContent = '')
    {
        $this->parse($robotsTxtContent);
    }

    public function parse(string $content): void
    {
        $lines = explode("\n", str_replace("\r", "", $content));
        $currentUserAgents = [];

        foreach ($lines as $line) {
            // Strip comments
            $line = trim(explode('#', $line)[0]);
            if (empty($line) || !str_contains($line, ':')) {
                continue;
            }

            [$directive, $value] = array_map('trim', explode(':', $line, 2));
            $directive = strtolower($directive);

            if ($directive === 'user-agent') {
                $currentUserAgents = [strtolower($value)];
            } elseif ($directive === 'sitemap') {
                if (!empty($value)) {
                    $this->sitemaps[] = $value;
                }
            } elseif (in_array($directive, ['disallow', 'allow', 'crawl-delay'])) {
                foreach ($currentUserAgents as $agent) {
                    if ($directive === 'crawl-delay') {
                        $this->crawlDelay = (int) $value;
                    } else {
                        $this->rules[$agent][] = [
                            'type' => $directive, // allow or disallow
                            'path' => $value,
                        ];
                    }
                }
            }
        }
    }

    /**
     * Check whether a specific URL is allowed to be crawled.
     */
    public function isAllowed(string $url, string $userAgent = '*'): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $agent = strtolower($userAgent);

        // Check specific agent first, fallback to wildcard '*'
        $agentRules = $this->rules[$agent] ?? ($this->rules['*'] ?? []);

        if (empty($agentRules)) {
            return true;
        }

        $matchedRule = null;
        $matchedLength = -1;

        foreach ($agentRules as $rule) {
            $rulePath = $rule['path'];
            if (empty($rulePath)) {
                // Empty disallow means everything allowed
                if ($rule['type'] === 'disallow') {
                    return true;
                }
                continue;
            }

            // Simple prefix and wildcard pattern matching
            $pattern = str_replace(
                ['\*', '\$'],
                ['.*', '$'],
                preg_quote($rulePath, '#')
            );

            if (preg_match('#^' . $pattern . '#', $path)) {
                if (strlen($rulePath) > $matchedLength) {
                    $matchedLength = strlen($rulePath);
                    $matchedRule = $rule;
                }
            }
        }

        if ($matchedRule && $matchedRule['type'] === 'disallow') {
            return false;
        }

        return true;
    }

    public function getSitemaps(): array
    {
        return array_unique($this->sitemaps);
    }

    public function getCrawlDelay(): ?int
    {
        return $this->crawlDelay;
    }
}
