<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentWorkspace = null;
        $workspaces = [];

        if ($user) {
            // Load user workspaces
            $workspaces = $user->workspaces()->get(['workspaces.id', 'workspaces.name', 'workspaces.slug'])->map(function ($ws) use ($user) {
                return [
                    'id' => $ws->id,
                    'name' => $ws->name,
                    'slug' => $ws->slug,
                    'role' => $ws->pivot->role,
                ];
            });

            if ($user->current_workspace_id) {
                $ws = $user->currentWorkspace;
                if ($ws) {
                    $membership = $ws->users()->where('users.id', $user->id)->first();
                    $currentWorkspace = [
                        'id' => $ws->id,
                        'name' => $ws->name,
                        'slug' => $ws->slug,
                        'owner_id' => $ws->owner_id,
                        'personal_team' => $ws->personal_team,
                        'role' => $membership ? $membership->pivot->role : ($user->is_platform_admin ? 'owner' : 'viewer'),
                    ];
                }
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_platform_admin' => (bool) $user->is_platform_admin,
                ] : null,
                'current_workspace' => $currentWorkspace,
                'workspaces' => $workspaces,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'app_mode' => config('app.install_mode', 'self_hosted'),
        ];
    }
}
