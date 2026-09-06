<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        return null;
    }

    public function view(User $user, Report $report): bool
    {
        return $user->workspaces()->where('workspaces.id', $report->workspace_id)->exists();
    }

    public function create(User $user): bool
    {
        $workspace = $user->currentWorkspace;
        return $workspace && $user->hasRole($workspace, ['owner', 'admin', 'specialist']);
    }

    public function delete(User $user, Report $report): bool
    {
        $workspace = $report->workspace;
        return $workspace && $user->hasRole($workspace, ['owner', 'admin']);
    }
}
