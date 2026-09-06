<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crawl extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'project_id',
        'workspace_id',
        'status',
        'crawl_depth',
        'max_pages',
        'pages_crawled',
        'pages_discovered',
        'duration_seconds',
        'health_score',
        'summary',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'health_score' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(CrawledPage::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(SeoFinding::class);
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
