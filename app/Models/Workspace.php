<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'personal_team',
        'is_active',
        'settings',
        'retention_days',
    ];

    protected $casts = [
        'personal_team' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
        'retention_days' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function crawls(): HasMany
    {
        return $this->hasMany(Crawl::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(SeoTask::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
