<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Report;
use App\Models\SeoTask;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_create_project_and_manage_tasks(): void
    {
        // 1. User Registration Flow
        $response = $this->post('/register', [
            'name' => 'SEO Specialist',
            'email' => 'specialist@seovy.test',
            'password' => 'SecureP@ss123!',
            'password_confirmation' => 'SecureP@ss123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'specialist@seovy.test')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->current_workspace_id);

        $workspace = Workspace::find($user->current_workspace_id);
        $this->assertNotNull($workspace);

        // 2. Project Creation Flow
        $projectResponse = $this->actingAs($user)->post('/projects', [
            'name' => 'Client Website',
            'start_url' => 'https://clientseosite.com',
            'target_country' => 'TR',
            'target_language' => 'tr',
            'timezone' => 'Europe/Istanbul',
            'crawl_settings' => [
                'max_pages' => 50,
                'max_depth' => 3,
                'respect_robots' => true,
            ],
        ]);

        $projectResponse->assertRedirect();
        $project = Project::where('domain', 'clientseosite.com')->first();
        $this->assertNotNull($project);
        $this->assertEquals($workspace->id, $project->workspace_id);

        // 3. Task Creation Flow
        $taskResponse = $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
            'title' => 'Fix Missing Title on Homepage',
            'description' => 'Add a 50-60 character title tag',
            'priority' => 'critical',
        ]);

        $taskResponse->assertRedirect();
        $task = SeoTask::where('project_id', $project->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals('open', $task->status);
        $this->assertEquals('critical', $task->priority);

        // Update task status
        $updateTask = $this->actingAs($user)->patch("/projects/{$project->id}/tasks/{$task->id}", [
            'status' => 'in_progress',
        ]);
        $updateTask->assertRedirect();
        $this->assertEquals('in_progress', $task->fresh()->status);

        // 4. Report Download Flow
        Storage::fake('local');
        $filePath = 'reports/test-report.pdf';
        Storage::disk('local')->put($filePath, 'dummy pdf content');

        $report = Report::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'title' => 'Monthly Audit Report',
            'type' => 'technical_seo',
            'format' => 'pdf',
            'file_path' => $filePath,
            'status' => 'completed',
        ]);

        $downloadResponse = $this->actingAs($user)->get("/projects/{$project->id}/reports/{$report->id}/download");
        $downloadResponse->assertOk();
    }

    public function test_unauthorized_user_cannot_download_report(): void
    {
        Storage::fake('local');
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => bcrypt('password123'),
        ]);
        $workspace = Workspace::create(['name' => 'Owner WS', 'slug' => 'owner-ws', 'owner_id' => $owner->id]);
        $workspace->users()->attach($owner->id, ['role' => 'owner']);
        $owner->current_workspace_id = $workspace->id;
        $owner->save();

        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Owner Site',
            'domain' => 'owner.com',
            'start_url' => 'https://owner.com',
        ]);

        $filePath = 'reports/owner-report.pdf';
        Storage::disk('local')->put($filePath, 'dummy content');

        $report = Report::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'title' => 'Confidential SEO Audit',
            'type' => 'technical_seo',
            'file_path' => $filePath,
            'format' => 'pdf',
            'status' => 'completed',
        ]);

        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
        ]);
        $otherWs = Workspace::create(['name' => 'Attacker WS', 'slug' => 'attacker-ws', 'owner_id' => $otherUser->id]);
        $otherWs->users()->attach($otherUser->id, ['role' => 'owner']);
        $otherUser->current_workspace_id = $otherWs->id;
        $otherUser->save();

        // Tampering ID request
        $response = $this->actingAs($otherUser)->get("/projects/{$project->id}/reports/{$report->id}/download");
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_user_can_edit_and_delete_project(): void
    {
        $user = User::create([
            'name' => 'Project Manager',
            'email' => 'pm@seovy.test',
            'password' => bcrypt('password123'),
        ]);
        $workspace = Workspace::create(['name' => 'PM WS', 'slug' => 'pm-ws', 'owner_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'owner']);
        $user->current_workspace_id = $workspace->id;
        $user->save();

        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Original Site',
            'domain' => 'original.com',
            'start_url' => 'https://original.com',
            'target_country' => 'US',
            'target_language' => 'en',
            'timezone' => 'UTC',
        ]);

        // 1. Edit project
        $editResponse = $this->actingAs($user)->patch("/projects/{$project->id}", [
            'name' => 'Updated Site Name',
            'start_url' => 'https://newdomain.com/blog',
            'target_country' => 'TR',
            'target_language' => 'tr',
            'timezone' => 'Europe/Istanbul',
            'crawl_settings' => [
                'max_depth' => 4,
                'max_pages' => 300,
                'respect_robots' => true,
                'follow_subdomains' => true,
            ],
        ]);

        $editResponse->assertRedirect(route('projects.show', $project->id));
        $project->refresh();
        $this->assertEquals('Updated Site Name', $project->name);
        $this->assertEquals('newdomain.com', $project->domain);
        $this->assertEquals('TR', $project->target_country);
        $this->assertEquals('Europe/Istanbul', $project->timezone);
        $this->assertEquals(4, $project->crawl_settings['max_depth']);
        $this->assertEquals(300, $project->crawl_settings['max_pages']);

        // 2. Delete project
        $deleteResponse = $this->actingAs($user)->delete("/projects/{$project->id}");
        $deleteResponse->assertRedirect(route('projects.index'));
        $this->assertNull(Project::find($project->id));
    }
}

