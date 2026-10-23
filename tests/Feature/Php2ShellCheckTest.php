<?php

namespace Tests\Feature;

use App\Models\Project;
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

    private function setupProject(string $role = 'owner'): Project
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Security', 'slug' => 'security-'.$user->id, 'owner_id' => $user->id]);
        $workspace->users()->attach($user, ['role' => $role]);
        $user->update(['current_workspace_id' => $workspace->id]);
        $this->actingAs($user);

        return Project::create(['workspace_id' => $workspace->id, 'name' => 'Example', 'domain' => 'example.com', 'start_url' => 'https://example.com']);
    }

    public function test_check_is_independent_of_seo_crawls_and_persists_the_latest_result(): void
    {
        $project = $this->setupProject();
        $this->mock(SafeHttpClient::class, function ($mock) {
            $mock->shouldReceive('get')->once()->with('https://example.com')->andReturn(
                new SafeHttpResponse('https://example.com/', 'https://example.com', 200, ['X-Powered-By' => 'PHP/8.4.26'], '', 20, 20)
            );
        });

        $this->post("/security/php2shell/{$project->id}")->assertRedirect('/security/php2shell?project='.$project->id);
        $this->get('/security/php2shell?project='.$project->id)->assertInertia(fn (Assert $page) => $page
            ->component('Security/Php2Shell')
            ->where('assessment.status', 'potential_risk')
            ->where('assessment.observations.0.version', '8.4.26')
            ->where('assessment.requested_url', 'https://example.com')
            ->where('assessment.checked_pages', 1));
        $this->assertDatabaseCount('crawls', 0);
        $this->assertDatabaseCount('seo_findings', 0);
    }

    public function test_viewer_cannot_start_checks(): void
    {
        $project = $this->setupProject('viewer');
        $this->mock(SafeHttpClient::class)->shouldNotReceive('get');
        $this->post("/security/php2shell/{$project->id}")->assertForbidden();
    }

    public function test_another_workspace_cannot_scan_or_see_a_project(): void
    {
        $foreignProject = $this->setupProject();
        $this->setupProject();
        $this->mock(SafeHttpClient::class)->shouldNotReceive('get');
        $this->post("/security/php2shell/{$foreignProject->id}")->assertNotFound();
        $this->get('/security/php2shell?project='.$foreignProject->id)->assertInertia(fn (Assert $page) => $page
            ->where('selectedProjectId', null)->where('assessment', null)->has('projects', 1));
    }

    public function test_connection_errors_do_not_create_a_safe_result_or_expose_exception_details(): void
    {
        $project = $this->setupProject();
        $this->mock(SafeHttpClient::class, function ($mock) {
            $mock->shouldReceive('get')->once()->andThrow(new \Exception('sensitive network details'));
        });
        $this->post("/security/php2shell/{$project->id}")->assertSessionHasErrors('scan');
        $this->get('/security/php2shell?project='.$project->id)->assertInertia(fn (Assert $page) => $page->where('assessment', null));
    }

    public function test_guests_cannot_access_checks(): void
    {
        $this->get('/security/php2shell')->assertRedirect('/login');
    }
}
