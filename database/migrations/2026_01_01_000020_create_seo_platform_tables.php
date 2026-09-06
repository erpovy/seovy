<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('domain')->index();
            $table->text('start_url');
            $table->string('target_country', 2)->default('TR');
            $table->string('target_language', 5)->default('tr');
            $table->string('timezone')->default('Europe/Istanbul');
            $table->json('crawl_settings')->nullable();
            $table->json('competitor_domains')->nullable();
            $table->string('verification_token', 64)->nullable();
            $table->timestamp('ownership_verified_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['workspace_id', 'domain']);
            $table->index(['workspace_id', 'is_archived']);
        });

        Schema::create('crawls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending, running, paused, completed, failed, cancelled
            $table->integer('crawl_depth')->default(3);
            $table->integer('max_pages')->default(500);
            $table->integer('pages_crawled')->default(0);
            $table->integer('pages_discovered')->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->decimal('health_score', 5, 2)->nullable();
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'project_id', 'status']);
            $table->index(['project_id', 'created_at']);
        });

        Schema::create('crawled_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_id')->constrained('crawls')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->text('url');
            $table->string('url_hash', 64)->index();
            $table->text('path');
            $table->smallInteger('status_code')->default(200);
            $table->string('content_type')->nullable();
            $table->integer('response_time_ms')->default(0);
            $table->text('title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('robots_meta')->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->integer('h1_count')->default(0);
            $table->json('h1_tags')->nullable();
            $table->smallInteger('depth')->default(0);
            $table->integer('inlinks_count')->default(0);
            $table->integer('outlinks_count')->default(0);
            $table->integer('word_count')->default(0);
            $table->json('schema_types')->nullable();
            $table->json('raw_headers')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['crawl_id', 'status_code']);
            $table->index(['crawl_id', 'is_indexable']);
            $table->index(['crawl_id', 'url_hash']);
        });

        Schema::create('seo_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_id')->constrained('crawls')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('crawled_page_id')->nullable()->constrained('crawled_pages')->cascadeOnDelete();
            $table->string('rule_code', 50)->index();
            $table->string('category', 30); // crawlability, indexability, meta, content, links, performance, security
            $table->string('severity', 20); // critical, warning, notice
            $table->string('title');
            $table->text('description');
            $table->json('evidence')->nullable();
            $table->text('recommendation');
            $table->timestamps();

            $table->index(['crawl_id', 'severity']);
            $table->index(['project_id', 'rule_code']);
            $table->index(['workspace_id', 'category']);
        });

        Schema::create('seo_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('seo_finding_id')->nullable()->constrained('seo_findings')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('medium'); // low, medium, high, critical
            $table->string('status', 20)->default('open'); // open, in_progress, completed, ignored
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->json('comments')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['workspace_id', 'assigned_to']);
        });

        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('keyword')->index();
            $table->text('target_url')->nullable();
            $table->json('tags')->nullable();
            $table->integer('search_volume')->nullable();
            $table->integer('current_position')->nullable();
            $table->integer('previous_position')->nullable();
            $table->json('history')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'keyword']);
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('type', 30); // gsc, ga4, pagespeed, serp_provider
            $table->text('credentials')->nullable(); // encrypted JSON
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'type']);
            $table->index(['workspace_id', 'type']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 30); // full_audit, technical_seo, content, executive
            $table->string('file_path')->nullable();
            $table->string('format', 10)->default('pdf'); // pdf, csv
            $table->string('status', 20)->default('completed'); // pending, generating, completed, failed
            $table->string('public_token', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'project_id']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('plan_name')->default('free'); // free, pro, agency
            $table->string('status')->default('active'); // active, trialing, past_due, canceled
            $table->string('stripe_id')->nullable()->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->json('limits')->nullable(); // max_projects, max_monthly_pages, max_keywords, team_members
            $table->timestamps();

            $table->unique('workspace_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('keywords');
        Schema::dropIfExists('seo_tasks');
        Schema::dropIfExists('seo_findings');
        Schema::dropIfExists('crawled_pages');
        Schema::dropIfExists('crawls');
        Schema::dropIfExists('projects');
    }
};
