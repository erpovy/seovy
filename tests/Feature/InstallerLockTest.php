<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstallerLockTest extends TestCase
{
    public function test_install_route_is_blocked_when_locked(): void
    {
        $lockFile = storage_path('installed.lock');
        file_put_contents($lockFile, json_encode(['locked' => true]));

        try {
            $response = $this->get('/install');
            $response->assertStatus(403);
        } finally {
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }
        }
    }
}
