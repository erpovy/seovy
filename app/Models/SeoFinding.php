<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoFinding extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'crawl_id',
        'project_id',
        'workspace_id',
        'crawled_page_id',
        'rule_code',
        'category',
        'severity',
        'title',
        'description',
        'evidence',
        'recommendation',
    ];

    protected $casts = [
        'evidence' => 'array',
    ];

    public function crawl(): BelongsTo
    {
        return $this->belongsTo(Crawl::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CrawledPage::class, 'crawled_page_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(SeoTask::class);
    }
}
