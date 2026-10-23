<?php

namespace App\Modules\Security;

use App\Modules\Crawler\SafeHttpResponse;

class Php2ShellDetector
{
    public const SOURCE = 'https://www.php.net/archive/2026.php';

    public const FIXED_VERSIONS = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];

    public const CVES = ['CVE-2026-109618', 'CVE-2026-109619'];

    /** Passive banner assessment only: headers cannot prove patch status or exploitability. */
    public function assess(array $headers): array
    {
        $observations = [];
        foreach ($headers as $name => $values) {
            if (! in_array(strtolower($name), ['x-powered-by', 'server'], true)) {
                continue;
            }

            foreach ((array) $values as $value) {
                preg_match_all('/\bPHP\/([0-9]+\.[0-9]+\.[0-9]+(?:-?(?:dev|alpha|beta|rc)[0-9]*)?)(?=$|[\s,;)\-+])/i', $value, $matches);
                foreach (array_unique($matches[1]) as $version) {
                    $branch = implode('.', array_slice(explode('.', $version), 0, 2));
                    $fixed = self::FIXED_VERSIONS[$branch] ?? null;
                    $status = 'unknown';
                    if ($fixed !== null) {
                        $status = version_compare($version, $fixed, '<') ? 'potential_risk' : 'fixed_version_reported';
                    } elseif (version_compare($version, '8.2.0', '<')) {
                        $status = 'potential_risk';
                    }
                    $observations[] = [
                        'header' => strtolower($name),
                        'version' => $version,
                        'status' => $status,
                        'fixed_version' => $fixed,
                    ];
                }
            }
        }

        $statuses = array_column($observations, 'status');
        $status = in_array('potential_risk', $statuses, true) ? 'potential_risk'
            : (empty($statuses) || in_array('unknown', $statuses, true) ? 'unknown' : 'fixed_version_reported');

        return ['status' => $status, 'observations' => $observations];
    }

    public function report(SafeHttpResponse $response): array
    {
        $assessment = $this->assess($response->headers);
        $counts = ['potential_risk' => 0, 'fixed_version_reported' => 0, 'unknown' => 0];
        $counts[$assessment['status']] = 1;

        return [
            'status' => $assessment['status'],
            'url' => $response->url,
            'requested_url' => $response->originalUrl,
            'http_status' => $response->statusCode,
            'checked_pages' => 1,
            'counts' => $counts,
            'observations' => array_map(fn (array $item) => $item + ['url' => $response->url], array_slice($assessment['observations'], 0, 20)),
            'method' => 'passive_http_headers',
            'cves' => self::CVES,
            'fixed_versions' => array_values(self::FIXED_VERSIONS),
            'source' => self::SOURCE,
            'advisory_date' => '2026-10-21',
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
