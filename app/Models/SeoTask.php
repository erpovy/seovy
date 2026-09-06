<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoTask extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'project_id',
        'seo_finding_id',
        'title',
        'description',
        'priority',
        'status',
        'assigned_to',
        'due_date',
        'comments',
    ];

    protected $casts = [
        'due_date' => 'date',
        'comments' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(SeoFinding::class, 'seo_finding_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
