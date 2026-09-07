<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'plan_name',
        'status',
        'stripe_id',
        'trial_ends_at',
        'current_period_end',
        'limits',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_end' => 'datetime',
        'limits' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function getLimits(): array
    {
        $plan = SubscriptionPlan::where('code', $this->plan_name)->first();
        if ($plan && is_array($plan->limits)) {
            $defaultLimits = $plan->limits;
        } else {
            $fallbackLimits = [
                'free' => ['max_projects' => 1, 'max_pages_monthly' => 500, 'max_keywords' => 10, 'team_members' => 1],
                'pro' => ['max_projects' => 5, 'max_pages_monthly' => 20000, 'max_keywords' => 100, 'team_members' => 5],
                'agency' => ['max_projects' => 50, 'max_pages_monthly' => 200000, 'max_keywords' => 2000, 'team_members' => 25],
            ];
            $defaultLimits = $fallbackLimits[$this->plan_name] ?? $fallbackLimits['free'];
        }

        return array_merge($defaultLimits, $this->limits ?? []);
    }
}
