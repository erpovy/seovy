<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'project_id',
        'title',
        'type',
        'file_path',
        'format',
        'status',
        'public_token',
        'token_expires_at',
        'settings',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'settings' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isPublicValid(): bool
    {
        return $this->public_token && (!$this->token_expires_at || $this->token_expires_at->isFuture());
    }
}
