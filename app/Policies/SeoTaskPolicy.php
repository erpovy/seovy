<?php

namespace App\Policies;

use App\Models\SeoTask;
use App\Models\User;

class SeoTaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        return null;
    }

    public function view(User $user, SeoTask $task): bool
    {
        return $user->workspaces()->where('workspaces.id', $task->workspace_id)->exists();
    }

    public function manage(User $user, SeoTask $task): bool
    {
        $workspace = $task->workspace;
        return $workspace && $user->hasRole($workspace, ['owner', 'admin', 'specialist']);
    }
}
