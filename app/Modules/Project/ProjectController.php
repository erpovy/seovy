<?php

namespace App\Modules\Project;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Project::class);

        $user = $request->user();
        $workspace = $user->currentWorkspace;

        $query = Project::where('workspace_id', $workspace->id)
            ->with(['latestCrawl'])
            ->withCount('tasks');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if ($request->has('archived') && $request->boolean('archived')) {
            $query->where('is_archived', true);
        } else {
            $query->where('is_archived', false);
        }

        $projects = $query->latest()->paginate(12)->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => $request->only(['search', 'archived']),
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Project::class);

        $workspace = $request->user()->currentWorkspace;
        $subscription = $workspace->subscription;

        // Check project limit if in SaaS mode
        if (config('app.install_mode') === 'saas' && $subscription) {
            $limits = $subscription->getLimits();
            $currentProjectsCount = Project::where('workspace_id', $workspace->id)->count();

            if ($currentProjectsCount >= $limits['max_projects']) {
                return redirect()->route('projects.index')->withErrors([
                    'error' => "Planınızın proje sınırına ({$limits['max_projects']}) ulaştınız. Lütfen paketinizi yükseltin.",
                ]);
            }
        }

        return Inertia::render('Projects/Create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Project::class);

        $workspace = $request->user()->currentWorkspace;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_url' => ['required', 'url'],
            'target_country' => ['required', 'string', 'size:2'],
            'target_language' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'string', 'timezone'],
            'crawl_settings' => ['nullable', 'array'],
            'crawl_settings.max_depth' => ['nullable', 'integer', 'min:1', 'max:10'],
            'crawl_settings.max_pages' => ['nullable', 'integer', 'min:5', 'max:10000'],
            'crawl_settings.respect_robots' => ['nullable', 'boolean'],
            'crawl_settings.follow_subdomains' => ['nullable', 'boolean'],
            'crawl_settings.rate_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $domain = parse_url($validated['start_url'], PHP_URL_HOST);

        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => $validated['name'],
            'domain' => strtolower($domain),
            'start_url' => $validated['start_url'],
            'target_country' => strtoupper($validated['target_country']),
            'target_language' => strtolower($validated['target_language']),
            'timezone' => $validated['timezone'],
            'crawl_settings' => $validated['crawl_settings'] ?? [],
            'verification_token' => 'seovy-site-verification=' . Str::random(32),
        ]);

        AuditLog::log('project.created', 'Project', $project->id, [
            'name' => $project->name,
            'domain' => $project->domain,
        ]);

        return redirect()->route('projects.show', $project->id)->with('success', 'Web sitesi projesi başarıyla eklendi.');
    }

    public function show(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $project->load(['latestCrawl']);

        $crawls = $project->crawls()->take(10)->get();

        $latestCrawl = $project->latestCrawl;
        $findingsSummary = [];

        if ($latestCrawl) {
            $findingsSummary = [
                'critical' => $latestCrawl->findings()->where('severity', 'critical')->count(),
                'warning' => $latestCrawl->findings()->where('severity', 'warning')->count(),
                'notice' => $latestCrawl->findings()->where('severity', 'notice')->count(),
                'categories' => $latestCrawl->findings()
                    ->selectRaw('category, count(*) as total')
                    ->groupBy('category')
                    ->pluck('total', 'category'),
            ];
        }

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'crawls' => $crawls,
            'latestCrawl' => $latestCrawl,
            'findingsSummary' => $findingsSummary,
            'canManage' => $request->user()->hasRole($project->workspace, ['owner', 'admin', 'specialist']),
        ]);
    }

    public function edit(Project $project)
    {
        Gate::authorize('update', $project);

        return Inertia::render('Projects/Edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_url' => ['required', 'url'],
            'target_country' => ['required', 'string', 'size:2'],
            'target_language' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'string', 'timezone'],
            'crawl_settings' => ['nullable', 'array'],
            'crawl_settings.max_depth' => ['nullable', 'integer', 'min:1', 'max:10'],
            'crawl_settings.max_pages' => ['nullable', 'integer', 'min:5', 'max:10000'],
            'crawl_settings.respect_robots' => ['nullable', 'boolean'],
            'crawl_settings.follow_subdomains' => ['nullable', 'boolean'],
            'competitor_domains' => ['nullable', 'array'],
            'is_archived' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['start_url'])) {
            $domain = parse_url($validated['start_url'], PHP_URL_HOST);
            $validated['domain'] = strtolower($domain);
        }

        $validated['target_country'] = strtoupper($validated['target_country']);
        $validated['target_language'] = strtolower($validated['target_language']);

        $project->update($validated);

        AuditLog::log('project.updated', 'Project', $project->id, $validated);

        return redirect()->route('projects.show', $project->id)->with('success', 'Web sitesi ayarları başarıyla güncellendi.');
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        AuditLog::log('project.deleted', 'Project', $project->id, ['name' => $project->name]);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proje silindi.');
    }

    public function verifyOwnership(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        // Verification via DNS TXT record or HTML meta tag
        $domain = $project->domain;
        $expectedToken = $project->verification_token;
        $verified = false;
        $methodUsed = '';

        // Check DNS TXT records
        $dnsRecords = @dns_get_record($domain, DNS_TXT);
        if ($dnsRecords && is_array($dnsRecords)) {
            foreach ($dnsRecords as $record) {
                if (isset($record['txt']) && str_contains($record['txt'], $expectedToken)) {
                    $verified = true;
                    $methodUsed = 'DNS TXT Kaydı';
                    break;
                }
            }
        }

        // If not verified by DNS, check HTML Meta tag on homepage
        if (!$verified) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SeovyVerification/1.0',
                    ])
                    ->get($project->start_url);

                if ($response->successful() && str_contains($response->body(), $expectedToken)) {
                    $verified = true;
                    $methodUsed = 'HTML Meta Etiketi';
                }
            } catch (\Throwable $e) {
                // Ignore network errors during verification check
            }
        }

        if ($verified) {
            $project->ownership_verified_at = now();
            $project->save();

            AuditLog::log('project.ownership_verified', 'Project', $project->id, ['method' => $methodUsed]);

            return back()->with('success', "Tebrikler! Site sahipliği {$methodUsed} ile başarıyla doğrulandı.");
        }

        return back()->with('error', "Doğrulama başarısız! Lütfen sitenizin <head> kısmına <meta name=\"seovy-verification\" content=\"{$expectedToken}\"> etiketini eklediğinizden veya DNS TXT kaydını oluşturduğunuzdan emin olun.");
    }

    public function downloadWordpressPlugin(Project $project)
    {
        Gate::authorize('view', $project);

        $pluginContent = <<<'PHP'
<?php
/**
 * Plugin Name: Seovy SEO Connector
 * Plugin URI: https://seo.artovy.com
 * Description: Seovy SEO platformu ile WordPress sitenizi tek tıkla bağlayın ve doğrulayın.
 * Version: 1.0.0
 * Author: Seovy Team
 * Author URI: https://seo.artovy.com
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

class Seovy_Connector {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'page_init']);
        add_action('wp_head', [$this, 'inject_verification_meta']);
    }

    public function add_plugin_page() {
        add_options_page(
            'Seovy SEO Bağlantısı',
            'Seovy SEO',
            'manage_options',
            'seovy-connector',
            [$this, 'create_admin_page']
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Seovy SEO Site Doğrulama</h1>
            <p>Seovy panelinizde projeniz için üretilen doğrulama kodunu (token) aşağıya yapıştırıp kaydedin.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields('seovy_option_group');
                do_settings_sections('seovy-connector-admin');
                submit_button('Kaydet ve Doğrulamayı Aktif Et');
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'seovy_option_group',
            'seovy_verification_token',
            [$this, 'sanitize']
        );

        add_settings_section(
            'setting_section_id',
            'Doğrulama Ayarları',
            null,
            'seovy-connector-admin'
        );

        add_settings_field(
            'seovy_verification_token',
            'Doğrulama Kodu (Verification Token)',
            [$this, 'token_callback'],
            'seovy-connector-admin',
            'setting_section_id'
        );
    }

    public function sanitize($input) {
        return sanitize_text_field($input);
    }

    public function token_callback() {
        $token = get_option('seovy_verification_token', 'TOKEN_PLACEHOLDER');
        printf(
            '<input type="text" id="seovy_verification_token" name="seovy_verification_token" value="%s" style="width: 500px;" />',
            isset($token) ? esc_attr($token) : ''
        );
        echo '<p class="description">Bu kod Seovy projeniz için otomatik tanımlanmıştır. Kaydet butonuna basmanız yeterlidir.</p>';
    }

    public function inject_verification_meta() {
        $token = get_option('seovy_verification_token', 'TOKEN_PLACEHOLDER');
        if (!empty($token)) {
            echo "\n<!-- Seovy SEO Verification -->\n";
            echo '<meta name="seovy-verification" content="' . esc_attr($token) . '" />' . "\n\n";
        }
    }
}

new Seovy_Connector();
PHP;

        $pluginContent = str_replace('TOKEN_PLACEHOLDER', $project->verification_token, $pluginContent);

        $zipFileName = "seovy-connector-{$project->domain}.zip";
        $tempZipPath = tempnam(sys_get_temp_dir(), 'seovy_wp_');

        $zip = new \ZipArchive();
        if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('seovy-connector/seovy-connector.php', $pluginContent);
            $zip->close();
        }

        return response()->download($tempZipPath, $zipFileName)->deleteFileAfterSend(true);
    }
}
