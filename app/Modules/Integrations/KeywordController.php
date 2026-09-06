<?php

namespace App\Modules\Integrations;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Keyword;
use App\Models\Project;
use App\Modules\Integrations\Contracts\SerpProviderInterface;
use App\Modules\Integrations\Providers\DataForSeoProvider;
use App\Modules\Integrations\Providers\MockSerpProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        $provider = $this->resolveProvider();

        return Inertia::render('Keywords/Index', [
            'project' => $project,
            'keywords' => $keywords,
            'providerConfigured' => $provider->isConfigured(),
            'providerName' => class_basename($provider),
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

        $provider = $this->resolveProvider();
        if (!$provider->isConfigured()) {
            return back()->withErrors([
                'error' => 'Canlı SERP sağlayıcı yapılandırılmamış. Sıralamaları CSV ile içe aktarabilir veya DataForSEO API bilgilerinizi tanımlayabilirsiniz.',
            ]);
        }

        $keywords = $project->keywords()->get();
        if ($keywords->isEmpty()) {
            return back()->withErrors(['error' => 'Takip edilen hiçbir anahtar kelime yok.']);
        }

        $kwList = $keywords->pluck('keyword')->toArray();
        $rankings = $provider->checkRankings($project->domain, $kwList, $project->target_country, $project->target_language);

        foreach ($keywords as $keywordModel) {
            $kwText = $keywordModel->keyword;
            if (isset($rankings[$kwText])) {
                $pos = $rankings[$kwText]['position'];
                $vol = $rankings[$kwText]['search_volume'];

                $history = $keywordModel->history ?? [];
                $history[] = [
                    'date' => date('Y-m-d'),
                    'position' => $pos,
                ];

                $keywordModel->update([
                    'previous_position' => $keywordModel->current_position,
                    'current_position' => $pos,
                    'search_volume' => $vol ?? $keywordModel->search_volume,
                    'history' => array_slice($history, -30), // keep last 30 checks
                ]);
            }
        }

        AuditLog::log('keywords.rankings_checked', 'Project', $project->id, ['count' => count($keywords)]);

        return back()->with('success', 'Anahtar kelime sıralamaları güncellendi.');
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

    protected function resolveProvider(): SerpProviderInterface
    {
        $providerType = config('services.serp.provider', env('SERP_PROVIDER', 'mock'));

        if ($providerType === 'dataforseo') {
            return new DataForSeoProvider();
        }

        return new MockSerpProvider();
    }
}
