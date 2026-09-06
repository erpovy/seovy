<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'ip_address',
        'user_agent',
        'details',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?string $resourceType = null, ?int $resourceId = null, ?array $details = null, ?int $workspaceId = null): self
    {
        $user = auth()->user();

        return static::create([
            'workspace_id' => $workspaceId ?? $user?->current_workspace_id,
            'user_id' => $user?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
