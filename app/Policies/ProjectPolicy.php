<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace !== null;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->workspaces()->where('workspaces.id', $project->workspace_id)->exists();
    }

    public function create(User $user): bool
    {
        $workspace = $user->currentWorkspace;
        return $workspace && $user->hasRole($workspace, ['owner', 'admin', 'specialist']);
    }

    public function update(User $user, Project $project): bool
    {
        $workspace = $project->workspace;
        return $workspace && $user->hasRole($workspace, ['owner', 'admin', 'specialist']);
    }

    public function delete(User $user, Project $project): bool
    {
        $workspace = $project->workspace;
        return $workspace && $user->hasRole($workspace, ['owner', 'admin']);
    }

    public function crawl(User $user, Project $project): bool
    {
        $workspace = $project->workspace;
        return $workspace && $user->hasRole($workspace, ['owner', 'admin', 'specialist']);
    }
}
