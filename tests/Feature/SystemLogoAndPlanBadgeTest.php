<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemLogoAndPlanBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@seovy.test',
            'password' => bcrypt('password123'),
            'is_platform_admin' => true,
        ]);

        $this->regularUser = User::create([
            'name' => 'SEO Specialist',
            'email' => 'seo@seovy.test',
            'password' => bcrypt('password123'),
            'is_platform_admin' => false,
        ]);

        $this->workspace = Workspace::create([
            'name' => 'Acme Agency',
            'slug' => 'acme-agency',
            'owner_id' => $this->regularUser->id,
        ]);
        $this->workspace->users()->attach($this->regularUser->id, ['role' => 'owner']);
        $this->regularUser->current_workspace_id = $this->workspace->id;
        $this->regularUser->save();

        SubscriptionPlan::ensureDefaultPlans();
    }

    public function test_admin_can_view_system_tab_with_system_settings(): void
    {
        SystemSetting::set('system_logo', 'https://example.com/logo.png');
        SystemSetting::set('brand_name', 'CustomSEO');

        $response = $this->actingAs($this->admin)->get('/admin?tab=system');
        $response->assertOk();
    }

    public function test_admin_can_update_brand_name_and_logo_url(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/settings/logo', [
            'logo_url' => 'https://cdn.example.com/company-logo.svg',
            'brand_name' => 'UltraSEO Enterprise',
        ]);

        $response->assertSessionHas('success');

        $this->assertEquals('https://cdn.example.com/company-logo.svg', SystemSetting::get('system_logo'));
        $this->assertEquals('UltraSEO Enterprise', SystemSetting::get('brand_name'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'system_settings.logo_updated',
        ]);
    }

    public function test_admin_can_upload_logo_file(): void
    {
        $file = UploadedFile::fake()->create('test_logo.png', 50, 'image/png');

        $response = $this->actingAs($this->admin)->post('/admin/settings/logo', [
            'logo_file' => $file,
            'brand_name' => 'FileLogo Corp',
        ]);

        $response->assertSessionHas('success');

        $logoPath = SystemSetting::get('system_logo');
        $this->assertNotNull($logoPath);
        $this->assertStringStartsWith('/uploads/branding/', $logoPath);

        // Verify physical file was created
        $this->assertTrue(File::exists(public_path($logoPath)));

        // Clean up created test file
        if (File::exists(public_path($logoPath))) {
            File::delete(public_path($logoPath));
        }
    }

    public function test_admin_can_upload_svg_logo_file(): void
    {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="40" viewBox="0 0 100 40"><text x="10" y="25" fill="#4f46e5">Logo</text></svg>';
        $file = UploadedFile::fake()->createWithContent('brand_logo.svg', $svgContent);

        $response = $this->actingAs($this->admin)->post('/admin/settings/logo', [
            'logo_file' => $file,
            'brand_name' => 'VectorBrand',
        ]);

        $response->assertSessionHas('success');

        $logoPath = SystemSetting::get('system_logo');
        $this->assertNotNull($logoPath);
        $this->assertStringStartsWith('/uploads/branding/', $logoPath);
        $this->assertStringEndsWith('.svg', $logoPath);

        // Verify physical file was created
        $this->assertTrue(File::exists(public_path($logoPath)));

        // Clean up created test file
        if (File::exists(public_path($logoPath))) {
            File::delete(public_path($logoPath));
        }
    }

    public function test_admin_can_upload_favicon_file(): void
    {
        $file = UploadedFile::fake()->create('custom_favicon.ico', 15, 'image/x-icon');

        $response = $this->actingAs($this->admin)->post('/admin/settings/logo', [
            'favicon_file' => $file,
        ]);

        $response->assertSessionHas('success');

        $faviconPath = SystemSetting::get('system_favicon');
        $this->assertNotNull($faviconPath);
        $this->assertStringStartsWith('/uploads/branding/favicon_', $faviconPath);
        $this->assertStringEndsWith('.ico', $faviconPath);

        // Verify physical file was created
        $this->assertTrue(File::exists(public_path($faviconPath)));

        // Clean up created test file
        if (File::exists(public_path($faviconPath))) {
            File::delete(public_path($faviconPath));
        }
    }

    public function test_admin_can_reset_logo_to_default(): void
    {
        SystemSetting::set('system_logo', '/uploads/branding/old_logo.png');
        SystemSetting::set('brand_name', 'OldBrand');

        $response = $this->actingAs($this->admin)->post('/admin/settings/logo', [
            'action' => 'reset',
        ]);

        $response->assertSessionHas('success');

        $this->assertNull(SystemSetting::get('system_logo'));
        $this->assertEquals('Seovy', SystemSetting::get('brand_name'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'system_settings.logo_reset',
        ]);
    }

    public function test_non_admin_cannot_update_logo(): void
    {
        $response = $this->actingAs($this->regularUser)->post('/admin/settings/logo', [
            'brand_name' => 'Hacker Brand',
        ]);

        $response->assertForbidden();
    }

    public function test_system_settings_and_workspace_plan_are_shared_globally(): void
    {
        SystemSetting::set('system_logo', '/custom-logo.png');
        SystemSetting::set('brand_name', 'Seovy Global');

        $this->workspace->subscription()->create([
            'plan_name' => 'pro',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->regularUser)->get('/dashboard');
        $response->assertOk();

        // Ensure page has shared props
        $pageProps = $response->original->getData()['page']['props'];
        $this->assertEquals('/custom-logo.png', $pageProps['system_settings']['logo']);
        $this->assertEquals('Seovy Global', $pageProps['system_settings']['brand_name']);
        $this->assertEquals('pro', $pageProps['auth']['current_workspace']['plan_code']);
        $this->assertEquals('Pro Plan', $pageProps['auth']['current_workspace']['plan_name']);
    }
}
