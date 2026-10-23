<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Modules\Crawler\SafeHttpClient;
use App\Modules\Crawler\SafeHttpResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Php2ShellCheckTest extends TestCase
{
    use RefreshDatabase;

    private function setupUser(string $role = 'owner'): User
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Security', 'slug' => 'security-'.$user->id, 'owner_id' => $user->id]);
        $workspace->users()->attach($user, ['role' => $role]);
        $user->update(['current_workspace_id' => $workspace->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_check_is_independent_of_seo_crawls_and_persists_the_latest_result(): void
    {
        $this->setupUser();
        $this->mock(SafeHttpClient::class, function ($mock) {
            $mock->shouldReceive('get')->once()->with('https://example.com')->andReturn(
                new SafeHttpResponse('https://example.com/', 'https://example.com', 200, ['X-Powered-By' => 'PHP/8.4.26'], '', 20, 20)
            );
        });

        $this->post('/security/php2shell', ['url' => 'https://example.com'])->assertRedirect('/security/php2shell');
        $this->get('/security/php2shell')->assertInertia(fn (Assert $page) => $page
            ->component('Security/Php2Shell')
            ->where('assessment.status', 'potential_risk')
            ->where('assessment.observations.0.version', '8.4.26')
            ->where('assessment.requested_url', 'https://example.com')
            ->where('assessment.checked_pages', 1));
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('crawls', 0);
        $this->assertDatabaseCount('seo_findings', 0);
    }

    public function test_viewer_cannot_start_checks(): void
    {
        $this->setupUser('viewer');
        $this->mock(SafeHttpClient::class)->shouldNotReceive('get');
        $this->post('/security/php2shell', ['url' => 'https://example.com'])->assertForbidden();
    }

    public function test_results_are_isolated_between_users_and_workspaces(): void
    {
        $firstUser = $this->setupUser();
        $this->mock(SafeHttpClient::class, function ($mock) {
            $mock->shouldReceive('get')->once()->andReturn(
                new SafeHttpResponse('https://example.com', 'https://example.com', 200, ['X-Powered-By' => 'PHP/8.4.26'], '', 20, 20)
            );
        });
        $this->post('/security/php2shell', ['url' => 'https://example.com']);
        $member = User::factory()->create(['current_workspace_id' => $firstUser->current_workspace_id]);
        $firstUser->currentWorkspace->users()->attach($member, ['role' => 'specialist']);
        $this->actingAs($member)->get('/security/php2shell')->assertInertia(fn (Assert $page) => $page->where('assessment', null));
        $this->setupUser();
        $this->get('/security/php2shell')->assertInertia(fn (Assert $page) => $page->where('assessment', null));
    }

    public function test_invalid_or_non_http_urls_are_rejected_before_fetching(): void
    {
        $this->setupUser();
        $this->mock(SafeHttpClient::class)->shouldNotReceive('get');
        foreach (['', 'not-a-url', 'file:///etc/passwd', 'ftp://example.com/file'] as $url) {
            $this->post('/security/php2shell', ['url' => $url])->assertSessionHasErrors('url');
        }
    }

    public function test_local_network_urls_are_blocked(): void
    {
        $this->setupUser();
        $this->post('/security/php2shell', ['url' => 'http://127.0.0.1'])->assertSessionHasErrors('scan');
        $this->get('/security/php2shell')->assertInertia(fn (Assert $page) => $page->where('assessment', null));
    }

    public function test_connection_errors_do_not_create_a_safe_result_or_expose_exception_details(): void
    {
        $this->setupUser();
        $this->mock(SafeHttpClient::class, function ($mock) {
            $mock->shouldReceive('get')->once()->andThrow(new \Exception('sensitive network details'));
        });
        $this->post('/security/php2shell', ['url' => 'https://example.com'])->assertSessionHasErrors('scan');
        $this->get('/security/php2shell')->assertInertia(fn (Assert $page) => $page->where('assessment', null));
    }

    public function test_guests_cannot_access_checks(): void
    {
        $this->get('/security/php2shell')->assertRedirect('/login');
    }
}
