<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'name',
        'domain',
        'start_url',
        'target_country',
        'target_language',
        'timezone',
        'crawl_settings',
        'competitor_domains',
        'verification_token',
        'ownership_verified_at',
        'is_archived',
    ];

    protected $casts = [
        'crawl_settings' => 'array',
        'competitor_domains' => 'array',
        'ownership_verified_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    public function crawls(): HasMany
    {
        return $this->hasMany(Crawl::class)->latest();
    }

    public function latestCrawl(): HasOne
    {
        return $this->hasOne(Crawl::class)->latestOfMany();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(SeoTask::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function getDefaultCrawlSettings(): array
    {
        return array_merge([
            'max_depth' => 3,
            'max_pages' => 500,
            'respect_robots' => true,
            'user_agent' => config('crawler.default_user_agent', 'SeovyBot/1.0 (+https://seovy.local/bot)'),
            'rate_limit' => 5, // req/sec
            'concurrency' => 5,
            'timeout' => 15,
            'follow_subdomains' => false,
            'include_patterns' => [],
            'exclude_patterns' => [],
        ], $this->crawl_settings ?? []);
    }
}
