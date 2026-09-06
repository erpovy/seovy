<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_project_from_another_workspace(): void
    {
        // User 1 & Workspace 1
        $user1 = User::create([
            'name' => 'User One',
            'email' => 'user1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $workspace1 = Workspace::create([
            'name' => 'Workspace One',
            'slug' => 'workspace-one',
            'owner_id' => $user1->id,
        ]);
        $workspace1->users()->attach($user1->id, ['role' => 'owner']);
        $user1->current_workspace_id = $workspace1->id;
        $user1->save();

        // Project belonging to Workspace 1
        $project1 = Project::create([
            'workspace_id' => $workspace1->id,
            'name' => 'Project One',
            'domain' => 'site1.com',
            'start_url' => 'https://site1.com',
        ]);

        // User 2 & Workspace 2
        $user2 = User::create([
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $workspace2 = Workspace::create([
            'name' => 'Workspace Two',
            'slug' => 'workspace-two',
            'owner_id' => $user2->id,
        ]);
        $workspace2->users()->attach($user2->id, ['role' => 'owner']);
        $user2->current_workspace_id = $workspace2->id;
        $user2->save();

        // 1. Policy check: User 2 is NOT authorized to view Project 1
        $this->assertFalse($user2->can('view', $project1));

        // 2. HTTP Request check: Direct ID tampering is blocked (404/403 due to global workspace scope)
        $response = $this->actingAs($user2)->get("/projects/{$project1->id}");
        $this->assertTrue(
            in_array($response->status(), [403, 404]),
            "Expected 403 or 404 but got {$response->status()}"
        );
    }
}
