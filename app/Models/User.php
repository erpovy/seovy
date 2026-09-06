<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_platform_admin',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'current_workspace_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'is_platform_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(SeoTask::class, 'assigned_to');
    }

    public function hasRole(Workspace $workspace, string|array $roles): bool
    {
        if ($this->is_platform_admin) {
            return true;
        }

        $roles = (array) $roles;
        $membership = $this->workspaces()->where('workspace_id', $workspace->id)->first();

        if (!$membership) {
            return false;
        }

        $userRole = $membership->pivot->role;

        // Role hierarchy: owner > admin > specialist > viewer
        if ($userRole === 'owner') {
            return true;
        }

        if (in_array($userRole, $roles)) {
            return true;
        }

        if ($userRole === 'admin' && in_array('specialist', $roles)) {
            return true;
        }

        if (in_array($userRole, ['admin', 'specialist']) && in_array('viewer', $roles)) {
            return true;
        }

        return false;
    }

    public function isOwnerOf(Workspace $workspace): bool
    {
        return $this->id === $workspace->owner_id;
    }
}
