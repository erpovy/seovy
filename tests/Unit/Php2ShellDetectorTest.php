<?php

namespace Tests\Unit;

use App\Modules\Security\Php2ShellDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class Php2ShellDetectorTest extends TestCase
{
    #[DataProvider('versions')]
    public function test_upstream_version_boundaries(string $version, string $expected): void
    {
        $result = (new Php2ShellDetector)->assess(['X-Powered-By' => ["PHP/{$version}"]]);
        $this->assertSame($expected, $result['status']);
        $this->assertSame($version, $result['observations'][0]['version']);
    }

    public static function versions(): array
    {
        return [
            ['8.2.34', 'potential_risk'], ['8.2.35', 'fixed_version_reported'],
            ['8.3.35', 'potential_risk'], ['8.3.36', 'fixed_version_reported'],
            ['8.4.26', 'potential_risk'], ['8.4.27', 'fixed_version_reported'],
            ['8.5.11', 'potential_risk'], ['8.5.12', 'fixed_version_reported'],
            ['8.5.13', 'fixed_version_reported'], ['8.2.9', 'potential_risk'],
            ['8.1.99', 'potential_risk'], ['7.4.33', 'potential_risk'],
            ['8.4.27RC1', 'potential_risk'], ['8.4.27-RC1', 'potential_risk'],
            ['8.6.0RC4', 'unknown'], ['9.0.0', 'unknown'],
        ];
    }

    public function test_hidden_partial_or_unrelated_headers_never_mean_safe(): void
    {
        $detector = new Php2ShellDetector;
        foreach ([[], ['Server' => 'nginx'], ['X-Powered-By' => 'PHP'], ['X-Powered-By' => 'PHP/8.4'], ['Set-Cookie' => 'PHP/8.4.27'], ['Server' => 'PHP/8.4.27garbage']] as $headers) {
            $this->assertSame('unknown', $detector->assess($headers)['status']);
        }
    }

    public function test_risky_banner_wins_over_a_patched_banner(): void
    {
        $result = (new Php2ShellDetector)->assess([
            'SERVER' => 'Apache/2.4 (Unix) PHP/8.4.27',
            'x-powered-by' => ['PHP/8.3.36', 'PHP/8.2.34-1ubuntu1'],
        ]);
        $this->assertSame('potential_risk', $result['status']);
        $this->assertCount(3, $result['observations']);
    }

    public function test_unknown_branch_prevents_a_fully_patched_result(): void
    {
        $result = (new Php2ShellDetector)->assess(['X-Powered-By' => ['PHP/8.4.27', 'PHP/9.0.0']]);
        $this->assertSame('unknown', $result['status']);
    }
}
