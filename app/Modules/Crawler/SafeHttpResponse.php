<?php

namespace App\Modules\Crawler;

class SafeHttpResponse
{
    public function __construct(
        public readonly string $url,
        public readonly string $originalUrl,
        public readonly int $statusCode,
        public readonly array $headers,
        public readonly string $body,
        public readonly int $responseTimeMs,
        public readonly int $totalDurationMs,
        public readonly array $redirectHistory = [],
    ) {}

    public function getHeaderLine(string $name): string
    {
        foreach ($this->headers as $headerName => $values) {
            if (strcasecmp($headerName, $name) === 0) {
                return is_array($values) ? implode(', ', $values) : (string) $values;
            }
        }
        return '';
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isRedirect(): bool
    {
        return !empty($this->redirectHistory);
    }

    public function getContentType(): string
    {
        $contentType = $this->getHeaderLine('content-type');
        return explode(';', $contentType)[0];
    }
}
