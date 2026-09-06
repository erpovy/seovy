<?php

namespace App\Modules\Crawler;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class SafeHttpClient
{
    protected Client $client;
    protected int $timeout;
    protected int $maxResponseBytes;
    protected string $userAgent;

    // Disallowed IP ranges (CIDRs)
    protected static array $blockedCidrs = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16', // Cloud metadata (AWS, GCP, Azure, DigitalOcean)
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.88.99.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '255.255.255.255/32',
    ];

    public function __construct(?int $timeout = null, ?int $maxResponseBytes = null, ?string $userAgent = null)
    {
        $this->timeout = $timeout ?? (int) env('CRAWLER_DEFAULT_TIMEOUT', 15);
        $this->maxResponseBytes = $maxResponseBytes ?? (int) env('CRAWLER_MAX_RESPONSE_BYTES', 5242880);
        $this->userAgent = $userAgent ?? (string) env('CRAWLER_USER_AGENT', 'SeovyBot/1.0 (+https://seovy.local/bot)');
    }

    /**
     * Validate a URL against SSRF vulnerabilities.
     * Throws an exception if the URL points to internal, loopback, or metadata services.
     */
    public static function validateUrl(string $url): string
    {
        $parts = parse_url($url);

        if (!$parts || !isset($parts['scheme']) || !isset($parts['host'])) {
            throw new Exception("Geçersiz URL yapısı: {$url}");
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'])) {
            throw new Exception("Desteklenmeyen protokol: {$scheme}. Yalnızca HTTP ve HTTPS izinlidir.");
        }

        // Port check
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, [80, 443, 8080, 8443])) {
            throw new Exception("Güvenlik nedeniyle {$port} portuna erişim engellendi.");
        }

        $host = strtolower($parts['host']);

        // Check for literal localhost or metadata names
        if (in_array($host, ['localhost', 'metadata.google.internal', 'instance-data', '169.254.169.254'])) {
            throw new Exception("Yerel veya dahili ana bilgisayarlara erişim yasaktır: {$host}");
        }

        // Resolve DNS
        $ips = @dns_get_record($host, DNS_A + DNS_AAAA);
        $resolvedIps = [];

        if ($ips && is_array($ips)) {
            foreach ($ips as $record) {
                if (isset($record['ip'])) {
                    $resolvedIps[] = $record['ip'];
                } elseif (isset($record['ipv6'])) {
                    $resolvedIps[] = $record['ipv6'];
                }
            }
        }

        // Fallback to gethostbynamel if dns_get_record returned empty
        if (empty($resolvedIps)) {
            $fallback = @gethostbynamel($host);
            if ($fallback) {
                $resolvedIps = $fallback;
            }
        }

        // If host is already an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $resolvedIps[] = $host;
        }

        if (empty($resolvedIps)) {
            throw new Exception("DNS çözümlemesi başarısız oldu: {$host}");
        }

        // Check every resolved IP against private / loopback / metadata ranges
        foreach ($resolvedIps as $ip) {
            if (static::isPrivateOrReservedIp($ip)) {
                throw new Exception("SSRF Koruması: {$host} ({$ip}) özel, yerel veya rezerve edilmiş bir IP adresine çözümlendi.");
            }
        }

        return $url;
    }

    /**
     * Check if an IP address belongs to private, loopback, or cloud metadata ranges.
     */
    public static function isPrivateOrReservedIp(string $ip): bool
    {
        // IPv6 checks
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Loopback ::1
            if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
                return true;
            }
            // Unique Local Address (fc00::/7)
            if (preg_match('/^[fF][c-dC-D]/', $ip)) {
                return true;
            }
            // Link-local (fe80::/10)
            if (preg_match('/^[fF][eE][89a-bA-B]/', $ip)) {
                return true;
            }
            // IPv4-mapped IPv6 (::ffff:127.0.0.1)
            if (str_starts_with(strtolower($ip), '::ffff:')) {
                $v4 = substr($ip, 7);
                return static::isPrivateOrReservedIp($v4);
            }
            return false;
        }

        // IPv4 checks
        $longIp = ip2long($ip);
        if ($longIp === false) {
            return true;
        }

        foreach (static::$blockedCidrs as $cidr) {
            [$subnet, $mask] = explode('/', $cidr);
            $longSubnet = ip2long($subnet);
            $longMask = ~((1 << (32 - (int) $mask)) - 1);

            if (($longIp & $longMask) === ($longSubnet & $longMask)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute a safe GET request with SSRF validation on redirects and response size limits.
     */
    public function get(string $url): SafeHttpResponse
    {
        static::validateUrl($url);

        $stack = HandlerStack::create();

        // Redirect validation middleware
        $maxRedirects = 5;
        $redirectCount = 0;
        $redirectHistory = [];

        $client = new Client([
            'handler' => $stack,
            'timeout' => $this->timeout,
            'connect_timeout' => 5,
            'allow_redirects' => false, // We manually validate every redirect step
            'http_errors' => false,
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'tr,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
            ],
            'verify' => true,
        ]);

        $currentUrl = $url;
        $startTime = microtime(true);

        while ($redirectCount <= $maxRedirects) {
            static::validateUrl($currentUrl);

            $reqStartTime = microtime(true);
            $response = $client->request('GET', $currentUrl, [
                'stream' => true,
            ]);
            $responseTimeMs = (int) round((microtime(true) - $reqStartTime) * 1000);

            $statusCode = $response->getStatusCode();

            // Handle 3xx redirects safely
            if (in_array($statusCode, [301, 302, 303, 307, 308])) {
                $location = $response->getHeaderLine('Location');
                if (empty($location)) {
                    break;
                }

                // Resolve relative redirect URL
                $nextUrl = UrlNormalizer::resolveRelativeUrl($currentUrl, $location);
                $redirectHistory[] = [
                    'from' => $currentUrl,
                    'to' => $nextUrl,
                    'status' => $statusCode,
                ];

                // Check for redirect loop
                if (in_array($nextUrl, array_column($redirectHistory, 'from'))) {
                    throw new Exception("Yönlendirme döngüsü (Redirect Loop) saptandı: {$currentUrl} -> {$nextUrl}");
                }

                $redirectCount++;
                if ($redirectCount > $maxRedirects) {
                    throw new Exception("Maksimum yönlendirme sınırı ({$maxRedirects}) aşıldı.");
                }

                $currentUrl = $nextUrl;
                continue;
            }

            // Read response body with maximum byte cutoff
            $bodyStream = $response->getBody();
            $bodyContent = '';
            $bytesRead = 0;

            while (!$bodyStream->eof()) {
                $chunk = $bodyStream->read(8192);
                $bytesRead += strlen($chunk);
                $bodyContent .= $chunk;

                if ($bytesRead > $this->maxResponseBytes) {
                    // Truncate and stop reading oversized responses
                    break;
                }
            }

            $totalDurationMs = (int) round((microtime(true) - $startTime) * 1000);

            return new SafeHttpResponse(
                url: $currentUrl,
                originalUrl: $url,
                statusCode: $statusCode,
                headers: $response->getHeaders(),
                body: $bodyContent,
                responseTimeMs: $responseTimeMs,
                totalDurationMs: $totalDurationMs,
                redirectHistory: $redirectHistory
            );
        }

        throw new Exception("Yönlendirme zinciri çözülemedi: {$url}");
    }
}
