<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstallerWizardTest extends TestCase
{
    protected function tearDown(): void
    {
        $lockFile = storage_path('installed.lock');
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }

        config(['app.test_ensure_installed' => false]);

        parent::tearDown();
    }

    public function test_installer_page_loads_with_requirements(): void
    {
        $response = $this->get('/install');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Install/Index')
            ->has('requirements')
            ->has('serverInfo')
            ->has('detectedUrl')
        );
    }

    public function test_database_connection_test_endpoint_with_sqlite(): void
    {
        $response = $this->postJson('/install/test-db', [
            'db_connection' => 'sqlite',
            'db_database' => ':memory:',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_database_connection_test_endpoint_fails_on_bad_credentials(): void
    {
        $response = $this->postJson('/install/test-db', [
            'db_connection' => 'mysql',
            'db_host' => '127.0.0.1',
            'db_port' => 9999,
            'db_database' => 'non_existent_db',
            'db_username' => 'invalid_user',
            'db_password' => 'invalid_pass',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_uninstalled_system_redirects_visitors_to_install_when_enforced(): void
    {
        config(['app.test_ensure_installed' => true]);

        $lockFile = storage_path('installed.lock');
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }

        $response = $this->get('/login');
        $response->assertRedirect('/install');
    }

    public function test_install_page_is_blocked_after_installed_lock(): void
    {
        $lockFile = storage_path('installed.lock');
        file_put_contents($lockFile, json_encode(['installed' => true]));

        $response = $this->get('/install');
        $response->assertStatus(403);
    }
}
