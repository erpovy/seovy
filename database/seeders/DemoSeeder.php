<?php

namespace Database\Seeders;

use App\Models\Crawl;
use App\Models\CrawledPage;
use App\Models\Keyword;
use App\Models\Project;
use App\Models\Report;
use App\Models\SeoFinding;
use App\Models\SeoTask;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Demo User
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@seovy.test'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('Demo123456!'),
                'is_platform_admin' => false,
            ]
        );

        // 2. Create Demo Workspace
        $workspace = Workspace::firstOrCreate(
            ['slug' => 'demo-agency'],
            [
                'name' => 'Acme SEO Agency',
                'owner_id' => $demoUser->id,
            ]
        );

        $workspace->users()->syncWithoutDetaching([$demoUser->id => ['role' => 'owner']]);
        $demoUser->current_workspace_id = $workspace->id;
        $demoUser->save();

        // 3. Create Demo Project
        $project = Project::firstOrCreate(
            ['domain' => 'demo-ecommerce.store'],
            [
                'workspace_id' => $workspace->id,
                'name' => 'Demo E-Commerce Store',
                'start_url' => 'https://demo-ecommerce.store',
                'target_country' => 'TR',
                'target_language' => 'tr',
                'timezone' => 'Europe/Istanbul',
                'crawl_settings' => [
                    'max_pages' => 100,
                    'max_depth' => 3,
                    'respect_robots' => true,
                ],
            ]
        );

        // 4. Create Demo Crawl
        $crawl = Crawl::create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'status' => 'completed',
            'health_score' => 78.5,
            'crawl_depth' => 3,
            'max_pages' => 100,
            'pages_crawled' => 25,
            'pages_discovered' => 32,
            'duration_seconds' => 180,
            'summary' => [
                'errors' => 2,
                'warnings' => 5,
                'notices' => 7,
            ],
            'started_at' => now()->subHours(4),
            'completed_at' => now()->subHours(4)->addMinutes(3),
        ]);

        // 5. Create Crawled Pages
        $pagesData = [
            ['url' => 'https://demo-ecommerce.store/', 'path' => '/', 'status_code' => 200, 'title' => 'Acme Store | Online Shopping', 'is_indexable' => true],
            ['url' => 'https://demo-ecommerce.store/products', 'path' => '/products', 'status_code' => 200, 'title' => 'All Products - Acme Store', 'is_indexable' => true],
            ['url' => 'https://demo-ecommerce.store/category/shoes', 'path' => '/category/shoes', 'status_code' => 200, 'title' => '', 'is_indexable' => true], // missing title
            ['url' => 'https://demo-ecommerce.store/old-sale', 'path' => '/old-sale', 'status_code' => 404, 'title' => 'Not Found', 'is_indexable' => false], // 404
            ['url' => 'https://demo-ecommerce.store/checkout', 'path' => '/checkout', 'status_code' => 200, 'title' => 'Checkout', 'is_indexable' => false], // noindex
        ];

        foreach ($pagesData as $p) {
            $page = CrawledPage::create([
                'crawl_id' => $crawl->id,
                'project_id' => $project->id,
                'workspace_id' => $workspace->id,
                'url' => $p['url'],
                'url_hash' => hash('sha256', $p['url']),
                'path' => $p['path'],
                'status_code' => $p['status_code'],
                'content_type' => 'text/html',
                'title' => $p['title'],
                'is_indexable' => $p['is_indexable'],
                'response_time_ms' => rand(120, 480),
                'word_count' => rand(300, 1200),
            ]);

            // Add Findings
            if ($p['status_code'] === 404) {
                SeoFinding::create([
                    'crawl_id' => $crawl->id,
                    'project_id' => $project->id,
                    'workspace_id' => $workspace->id,
                    'crawled_page_id' => $page->id,
                    'rule_code' => 'http_404',
                    'category' => 'indexability',
                    'severity' => 'critical',
                    'title' => '404 Kırık Sayfa Tespit Edildi',
                    'description' => 'Sayfa 404 Not Found yanıtı döndürüyor.',
                    'recommendation' => 'Bu sayfaya gelen iç linkleri güncelleyin veya 301 yönlendirmesi ekleyin.',
                ]);
            }

            if (empty($p['title'])) {
                $finding = SeoFinding::create([
                    'crawl_id' => $crawl->id,
                    'project_id' => $project->id,
                    'workspace_id' => $workspace->id,
                    'crawled_page_id' => $page->id,
                    'rule_code' => 'missing_title',
                    'category' => 'meta',
                    'severity' => 'critical',
                    'title' => 'Eksik Title (Başlık) Etiketi',
                    'description' => 'Sayfada herhangi bir <title> etiketi bulunamadı.',
                    'recommendation' => '50-60 karakter uzunluğunda anahtar kelime içeren özgün bir başlık ekleyin.',
                ]);

                // Create Task for this finding
                SeoTask::create([
                    'workspace_id' => $workspace->id,
                    'project_id' => $project->id,
                    'seo_finding_id' => $finding->id,
                    'title' => 'Kategori sayfasına title etiketi ekle',
                    'description' => 'https://demo-ecommerce.store/category/shoes adresine SEO başlığı eklenmeli.',
                    'priority' => 'critical',
                    'status' => 'open',
                ]);
            }
        }

        // 6. Create Demo Keywords
        $keywords = [
            ['keyword' => 'online ayakkabı siparişi', 'current_position' => 4, 'previous_position' => 6, 'search_volume' => 12500],
            ['keyword' => 'uygun fiyatlı spor ayakkabı', 'current_position' => 11, 'previous_position' => 9, 'search_volume' => 8400],
            ['keyword' => 'kadın deri çizme modelleri', 'current_position' => 2, 'previous_position' => 2, 'search_volume' => 5600],
            ['keyword' => 'erkek koşu ayakkabısı', 'current_position' => 18, 'previous_position' => 14, 'search_volume' => 9100],
        ];

        foreach ($keywords as $kw) {
            Keyword::create([
                'workspace_id' => $workspace->id,
                'project_id' => $project->id,
                'keyword' => $kw['keyword'],
                'current_position' => $kw['current_position'],
                'previous_position' => $kw['previous_position'],
                'search_volume' => $kw['search_volume'],
            ]);
        }
    }
}
