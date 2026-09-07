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
                    $subscription = $ws->subscription;
                    $planCode = $subscription?->plan_name ?? 'free';
                    $planModel = \App\Models\SubscriptionPlan::where('code', $planCode)->first();
                    $planTitle = $planModel ? $planModel->name : ucfirst($planCode);

                    $currentWorkspace = [
                        'id' => $ws->id,
                        'name' => $ws->name,
                        'slug' => $ws->slug,
                        'owner_id' => $ws->owner_id,
                        'personal_team' => $ws->personal_team,
                        'role' => $membership ? $membership->pivot->role : ($user->is_platform_admin ? 'owner' : 'viewer'),
                        'plan_code' => $planCode,
                        'plan_name' => $planTitle,
                        'subscription' => $subscription ? [
                            'plan_name' => $subscription->plan_name,
                            'status' => $subscription->status,
                            'current_period_end' => $subscription->current_period_end,
                        ] : null,
                    ];
                }
            }
        }

        return [
            ...parent::share($request),
            'system_settings' => [
                'logo' => \App\Models\SystemSetting::get('system_logo_dark') ?: \App\Models\SystemSetting::get('system_logo', null),
                'logo_dark' => \App\Models\SystemSetting::get('system_logo_dark') ?: \App\Models\SystemSetting::get('system_logo', null),
                'logo_light' => \App\Models\SystemSetting::get('system_logo_light', null),
                'favicon' => \App\Models\SystemSetting::get('system_favicon', null),
                'brand_name' => \App\Models\SystemSetting::get('brand_name', 'Seovy'),
            ],
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
            'locale' => function () use ($request) {
                $loc = $request->session()->get('locale', $request->cookie('seovy_locale', config('app.locale', 'en')));
                app()->setLocale($loc);
                return $loc;
            },
            'app_mode' => config('app.install_mode', 'self_hosted'),
        ];
    }
}
