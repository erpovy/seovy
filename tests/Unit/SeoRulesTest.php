<?php

namespace Tests\Unit;

use App\Modules\Analyzer\SeoRuleEngine;
use App\Modules\Crawler\SafeHttpResponse;
use PHPUnit\Framework\TestCase;

class SeoRulesTest extends TestCase
{
    protected SeoRuleEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SeoRuleEngine();
    }

    public function test_detects_404_not_found(): void
    {
        $response = new SafeHttpResponse(
            url: 'https://example.com/missing-page',
            originalUrl: 'https://example.com/missing-page',
            statusCode: 404,
            headers: ['content-type' => ['text/html']],
            body: '<html><head><title>Not Found</title></head><body>404 Not Found</body></html>',
            responseTimeMs: 80,
            totalDurationMs: 80
        );

        $result = $this->engine->analyze($response, 'example.com');
        $ruleCodes = array_column($result['findings'], 'rule_code');

        $this->assertContains('STATUS_4XX_CLIENT_ERROR', $ruleCodes);
    }

    public function test_detects_missing_title_and_missing_h1(): void
    {
        $response = new SafeHttpResponse(
            url: 'https://example.com/test',
            originalUrl: 'https://example.com/test',
            statusCode: 200,
            headers: ['content-type' => ['text/html']],
            body: '<html><head></head><body><p>No title and no heading here</p></body></html>',
            responseTimeMs: 50,
            totalDurationMs: 50
        );

        $result = $this->engine->analyze($response, 'example.com');
        $ruleCodes = array_column($result['findings'], 'rule_code');

        $this->assertContains('TITLE_MISSING', $ruleCodes);
        $this->assertContains('H1_MISSING', $ruleCodes);
    }

    public function test_detects_multiple_h1_headings(): void
    {
        $response = new SafeHttpResponse(
            url: 'https://example.com/test',
            originalUrl: 'https://example.com/test',
            statusCode: 200,
            headers: ['content-type' => ['text/html']],
            body: '<html><head><title>Geçerli Bir Başlık Örneği ve Test Sayfası</title></head><body><h1>Birinci H1</h1><h1>İkinci H1</h1></body></html>',
            responseTimeMs: 50,
            totalDurationMs: 50
        );

        $result = $this->engine->analyze($response, 'example.com');
        $ruleCodes = array_column($result['findings'], 'rule_code');

        $this->assertContains('H1_MULTIPLE', $ruleCodes);
    }

    public function test_detects_missing_canonical_and_images_without_alt(): void
    {
        $response = new SafeHttpResponse(
            url: 'https://example.com/test',
            originalUrl: 'https://example.com/test',
            statusCode: 200,
            headers: ['content-type' => ['text/html']],
            body: '<html><head><title>Test Sayfası Başlığı Uzunluğu Gayet Uygun</title></head><body><h1>Ana Başlık</h1><img src="banner.jpg"></body></html>',
            responseTimeMs: 50,
            totalDurationMs: 50
        );

        $result = $this->engine->analyze($response, 'example.com');
        $ruleCodes = array_column($result['findings'], 'rule_code');

        $this->assertContains('CANONICAL_MISSING', $ruleCodes);
        $this->assertContains('IMAGES_MISSING_ALT', $ruleCodes);
    }

    public function test_health_score_calculation_formula(): void
    {
        // 100 pages, 0 issues -> 100%
        $perfectScore = SeoRuleEngine::calculateHealthScore(100, 0, 0, 0);
        $this->assertEquals(100.0, $perfectScore);

        // With critical penalty
        $penalizedScore = SeoRuleEngine::calculateHealthScore(100, 5, 2, 1);
        $this->assertLessThan(100.0, $penalizedScore);
        $this->assertGreaterThan(0.0, $penalizedScore);
    }
}
