<?php

namespace App\Modules\Integrations;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Integration;
use App\Models\Keyword;
use App\Models\Project;
use App\Modules\Integrations\Contracts\SerpProviderInterface;
use App\Modules\Integrations\Providers\DataForSeoProvider;
use App\Modules\Integrations\Providers\MockSerpProvider;
use App\Modules\Integrations\Providers\SerpApiProvider;
use App\Modules\Integrations\Providers\SmartWebSerpProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class KeywordController extends Controller
{
    public function index(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $query = $project->keywords();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('keyword', 'like', "%{$search}%");
        }

        $keywords = $query->latest()->paginate(25)->withQueryString();

        $provider = $this->resolveProvider($project);
        $serpIntegration = $project->integrations()->where('type', 'serp_provider')->first();

        return Inertia::render('Keywords/Index', [
            'project' => $project,
            'keywords' => $keywords,
            'providerConfigured' => $provider->isConfigured(),
            'providerName' => class_basename($provider),
            'serpIntegration' => [
                'provider' => $serpIntegration?->settings['provider'] ?? 'smart',
                'is_active' => $serpIntegration?->is_active ?? true,
                'has_credentials' => !empty($serpIntegration?->credentials),
            ],
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'target_url' => ['nullable', 'url'],
            'tags' => ['nullable', 'array'],
        ]);

        $kw = Keyword::create([
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
            'keyword' => trim($validated['keyword']),
            'target_url' => $validated['target_url'] ?? null,
            'tags' => $validated['tags'] ?? [],
        ]);

        AuditLog::log('keyword.added', 'Keyword', $kw->id, ['keyword' => $kw->keyword]);

        return back()->with('success', "'{$kw->keyword}' takibe alındı.");
    }

    public function checkRankings(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $keywords = $project->keywords()->get();
        if ($keywords->isEmpty()) {
            return back()->withErrors(['error' => 'Takip edilen hiçbir anahtar kelime bulunmuyor. Önce anahtar kelime ekleyin.']);
        }

        $provider = $this->resolveProvider($project);
        if (!$provider->isConfigured()) {
            return back()->withErrors([
                'error' => 'Seçilen SERP sağlayıcı yapılandırılmamış. Lütfen SERP Ayarları üzerinden bilgilerinizi güncelleyin.',
            ]);
        }

        try {
            $kwList = $keywords->pluck('keyword')->toArray();
            $country = $project->target_country ?: 'TR';
            $language = $project->target_language ?: 'tr';

            $rankings = $provider->checkRankings($project->domain, $kwList, $country, $language);

            $updatedCount = 0;
            foreach ($keywords as $keywordModel) {
                $kwText = $keywordModel->keyword;
                if (isset($rankings[$kwText])) {
                    $pos = $rankings[$kwText]['position'] ?? null;
                    $vol = $rankings[$kwText]['search_volume'] ?? null;
                    $targetUrl = $rankings[$kwText]['url'] ?? null;

                    $history = $keywordModel->history ?? [];
                    $history[] = [
                        'date' => date('Y-m-d H:i'),
                        'position' => $pos,
                    ];

                    $keywordModel->update([
                        'previous_position' => $keywordModel->current_position,
                        'current_position' => $pos,
                        'search_volume' => $vol ?? $keywordModel->search_volume,
                        'target_url' => $targetUrl ?: $keywordModel->target_url,
                        'history' => array_slice($history, -30), // keep last 30 checks
                    ]);
                    $updatedCount++;
                }
            }

            AuditLog::log('keywords.rankings_checked', 'Project', $project->id, [
                'count' => count($keywords),
                'updated' => $updatedCount,
                'provider' => class_basename($provider),
            ]);

            return back()->with('success', "{$updatedCount} anahtar kelimenin sıralaması başarıyla güncellendi.");
        } catch (\Throwable $e) {
            Log::error("SERP Check Error: " . $e->getMessage());
            return back()->withErrors(['error' => 'Sıralama kontrolü sırasında bir hata oluştu: ' . $e->getMessage()]);
        }
    }

    public function saveSerpSettings(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:smart,dataforseo,serpapi,mock'],
            'dataforseo_login' => ['nullable', 'string'],
            'dataforseo_password' => ['nullable', 'string'],
            'serpapi_key' => ['nullable', 'string'],
        ]);

        $credentials = [];
        if ($validated['provider'] === 'dataforseo') {
            $credentials = [
                'login' => $validated['dataforseo_login'] ?? '',
                'password' => $validated['dataforseo_password'] ?? '',
            ];
        } elseif ($validated['provider'] === 'serpapi') {
            $credentials = [
                'api_key' => $validated['serpapi_key'] ?? '',
            ];
        }

        Integration::updateOrCreate(
            ['project_id' => $project->id, 'type' => 'serp_provider'],
            [
                'workspace_id' => $project->workspace_id,
                'credentials' => !empty($credentials) ? $credentials : null,
                'settings' => [
                    'provider' => $validated['provider'],
                ],
                'is_active' => true,
                'last_synced_at' => now(),
            ]
        );

        AuditLog::log('keywords.serp_settings_saved', 'Project', $project->id, ['provider' => $validated['provider']]);

        return back()->with('success', 'SERP sağlayıcı ayarları başarıyla kaydedildi.');
    }

    public function importCsv(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $importedCount = 0;

        // Skip header if present
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;
            $kw = trim($row[0]);
            $position = isset($row[1]) && is_numeric($row[1]) ? (int) $row[1] : null;
            $volume = isset($row[2]) && is_numeric($row[2]) ? (int) $row[2] : null;

            Keyword::updateOrCreate(
                ['project_id' => $project->id, 'keyword' => $kw],
                [
                    'workspace_id' => $project->workspace_id,
                    'current_position' => $position,
                    'search_volume' => $volume,
                ]
            );
            $importedCount++;
        }

        fclose($handle);

        AuditLog::log('keywords.csv_imported', 'Project', $project->id, ['count' => $importedCount]);

        return back()->with('success', "{$importedCount} anahtar kelime CSV'den başarıyla içe aktarıldı.");
    }

    public function destroy(Project $project, Keyword $keyword)
    {
        Gate::authorize('update', $project);

        $keyword->delete();

        return back()->with('success', 'Anahtar kelime silindi.');
    }

    protected function resolveProvider(?Project $project = null): SerpProviderInterface
    {
        // 1. Check Project-level integration
        if ($project) {
            $integration = $project->integrations()->where('type', 'serp_provider')->first();
            if ($integration && $integration->is_active) {
                $providerType = $integration->settings['provider'] ?? 'smart';
                $creds = $integration->credentials ?? [];

                if ($providerType === 'dataforseo') {
                    return new DataForSeoProvider($creds['login'] ?? null, $creds['password'] ?? null);
                }
                if ($providerType === 'serpapi') {
                    return new SerpApiProvider($creds['api_key'] ?? null);
                }
                if ($providerType === 'mock') {
                    return new MockSerpProvider();
                }
                if ($providerType === 'smart') {
                    return new SmartWebSerpProvider($project);
                }
            }
        }

        // 2. Check Global / Environment Configuration
        $globalType = config('services.serp.provider', env('SERP_PROVIDER'));
        if ($globalType === 'dataforseo' || (!empty(env('DATAFORSEO_LOGIN')) && !empty(env('DATAFORSEO_PASSWORD')))) {
            return new DataForSeoProvider();
        }
        if ($globalType === 'serpapi' || !empty(env('SERPAPI_KEY'))) {
            return new SerpApiProvider();
        }
        if ($globalType === 'mock') {
            return new MockSerpProvider();
        }

        // 3. Default: Smart Web SERP Provider (Always working & zero config needed)
        return new SmartWebSerpProvider($project);
    }
}
