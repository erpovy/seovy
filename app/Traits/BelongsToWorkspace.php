<?php

namespace App\Traits;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToWorkspace
{
    /**
     * Boot the BelongsToWorkspace trait for a model.
     */
    protected static function bootBelongsToWorkspace(): void
    {
        static::creating(function ($model) {
            if (empty($model->workspace_id)) {
                $user = Auth::user();
                if ($user && $user->current_workspace_id) {
                    $model->workspace_id = $user->current_workspace_id;
                }
            }
        });

        static::addGlobalScope('workspace', function (Builder $builder) {
            $user = Auth::user();
            if ($user && $user->current_workspace_id && !$user->is_platform_admin) {
                $builder->where($builder->getModel()->getTable() . '.workspace_id', $user->current_workspace_id);
            }
        });
    }

    /**
     * Get the workspace that owns the model.
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
