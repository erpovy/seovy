<?php

namespace App\Policies;

use App\Models\Crawl;
use App\Models\User;

class CrawlPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        return null;
    }

    public function view(User $user, Crawl $crawl): bool
    {
        return $user->workspaces()->where('workspaces.id', $crawl->workspace_id)->exists();
    }

    public function manage(User $user, Crawl $crawl): bool
    {
        $workspace = $crawl->workspace;
        return $workspace && $user->hasRole($workspace, ['owner', 'admin', 'specialist']);
    }
}
