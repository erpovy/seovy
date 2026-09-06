<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawledPage extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'crawl_id',
        'project_id',
        'workspace_id',
        'url',
        'url_hash',
        'path',
        'status_code',
        'content_type',
        'response_time_ms',
        'title',
        'meta_description',
        'canonical_url',
        'robots_meta',
        'is_indexable',
        'h1_count',
        'h1_tags',
        'depth',
        'inlinks_count',
        'outlinks_count',
        'word_count',
        'schema_types',
        'raw_headers',
        'content_hash',
    ];

    protected $casts = [
        'h1_tags' => 'array',
        'schema_types' => 'array',
        'raw_headers' => 'array',
        'is_indexable' => 'boolean',
        'status_code' => 'integer',
        'response_time_ms' => 'integer',
        'depth' => 'integer',
        'inlinks_count' => 'integer',
        'outlinks_count' => 'integer',
        'word_count' => 'integer',
    ];

    public function crawl(): BelongsTo
    {
        return $this->belongsTo(Crawl::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(SeoFinding::class);
    }
}
