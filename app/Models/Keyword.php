<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keyword extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'project_id',
        'keyword',
        'target_url',
        'tags',
        'search_volume',
        'current_position',
        'previous_position',
        'history',
    ];

    protected $casts = [
        'tags' => 'array',
        'history' => 'array',
        'search_volume' => 'integer',
        'current_position' => 'integer',
        'previous_position' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
