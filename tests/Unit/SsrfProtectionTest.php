<?php

namespace Tests\Unit;

use App\Modules\Crawler\SafeHttpClient;
use Exception;
use PHPUnit\Framework\TestCase;

class SsrfProtectionTest extends TestCase
{
    public function test_blocks_localhost_and_loopback_ips(): void
    {
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('127.0.0.1'));
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('127.0.1.1'));
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('::1'));

        $this->expectException(Exception::class);
        SafeHttpClient::validateUrl('http://localhost/admin');
    }

    public function test_blocks_cloud_metadata_ip(): void
    {
        // 169.254.169.254 AWS / GCP / Azure metadata service
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('169.254.169.254'));

        $this->expectException(Exception::class);
        SafeHttpClient::validateUrl('http://169.254.169.254/latest/meta-data/');
    }

    public function test_blocks_private_ipv4_ranges(): void
    {
        // 10.0.0.0/8
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('10.0.0.1'));
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('10.254.254.254'));

        // 172.16.0.0/12
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('172.16.0.1'));
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('172.31.255.255'));

        // 192.168.0.0/16
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('192.168.1.1'));
        $this->assertTrue(SafeHttpClient::isPrivateOrReservedIp('192.168.100.50'));
    }

    public function test_blocks_invalid_protocols(): void
    {
        $this->expectException(Exception::class);
        SafeHttpClient::validateUrl('file:///etc/passwd');
    }

    public function test_blocks_forbidden_ports(): void
    {
        $this->expectException(Exception::class);
        SafeHttpClient::validateUrl('http://example.com:22/');
    }
}
