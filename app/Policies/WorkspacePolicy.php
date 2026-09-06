<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        return null;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $user->workspaces()->where('workspaces.id', $workspace->id)->exists();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->hasRole($workspace, ['owner', 'admin']);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->isOwnerOf($workspace);
    }

    public function invite(User $user, Workspace $workspace): bool
    {
        return $user->hasRole($workspace, ['owner', 'admin']);
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $user->hasRole($workspace, ['owner', 'admin']);
    }
}
