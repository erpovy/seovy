<?php

namespace App\Modules\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Crawl;
use App\Models\Project;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $reports = $project->reports()->latest()->paginate(15);

        return Inertia::render('Reports/Index', [
            'project' => $project,
            'reports' => $reports,
        ]);
    }

    /**
     * Generate dynamic CSV audit export.
     */
    public function exportCsv(Request $request, Project $project, Crawl $crawl)
    {
        Gate::authorize('view', $project);

        $pages = $crawl->pages()->with('findings')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="seovy-' . $project->domain . '-audit-' . date('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($pages) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($handle, [
                'URL',
                'Durum Kodu',
                'İndekslenebilir',
                'Başlık (Title)',
                'Başlık Uzunluğu',
                'Meta Açıklama',
                'Açıklama Uzunluğu',
                'H1 Sayısı',
                'Kelime Sayısı',
                'Kritik Sorun Sayısı',
                'Uyarı Sayısı',
                'Öneri Sayısı',
                'Bulgu Kodları',
            ]);

            foreach ($pages as $page) {
                $critical = $page->findings->where('severity', 'critical')->count();
                $warning = $page->findings->where('severity', 'warning')->count();
                $notice = $page->findings->where('severity', 'notice')->count();
                $ruleCodes = $page->findings->pluck('rule_code')->implode('; ');

                fputcsv($handle, [
                    $page->url,
                    $page->status_code,
                    $page->is_indexable ? 'Evet' : 'Hayır',
                    $page->title,
                    mb_strlen($page->title ?? ''),
                    $page->meta_description,
                    mb_strlen($page->meta_description ?? ''),
                    $page->h1_count,
                    $page->word_count,
                    $critical,
                    $warning,
                    $notice,
                    $ruleCodes,
                ]);
            }

            fclose($handle);
        };

        AuditLog::log('report.csv_downloaded', 'Crawl', $crawl->id);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate PDF Audit Report.
     */
    public function generatePdf(Request $request, Project $project, Crawl $crawl)
    {
        Gate::authorize('view', $project);

        $workspace = $project->workspace;
        $criticalCount = $crawl->findings()->where('severity', 'critical')->count();
        $warningCount = $crawl->findings()->where('severity', 'warning')->count();
        $noticeCount = $crawl->findings()->where('severity', 'notice')->count();

        $topFindings = $crawl->findings()->with('page:id,url')->latest()->take(20)->get();

        $data = [
            'project' => $project,
            'workspace' => $workspace,
            'crawl' => $crawl,
            'stats' => [
                'health_score' => $crawl->health_score ?? 100,
                'total_pages' => $crawl->pages_crawled,
                'critical_count' => $criticalCount,
                'warning_count' => $warningCount,
                'notice_count' => $noticeCount,
            ],
            'topFindings' => $topFindings,
            'generated_at' => now()->format('d.m.Y H:i'),
        ];

        $pdf = Pdf::loadView('reports.audit_pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $fileName = "reports/audit_{$project->domain}_crawl_{$crawl->id}_" . time() . ".pdf";
        Storage::disk('local')->put($fileName, $pdf->output());

        $report = Report::create([
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
            'title' => "{$project->name} - Teknik SEO Raporu",
            'type' => 'technical_seo',
            'file_path' => $fileName,
            'format' => 'pdf',
            'status' => 'completed',
            'public_token' => Str::random(40),
            'token_expires_at' => now()->addDays(30),
        ]);

        AuditLog::log('report.pdf_generated', 'Report', $report->id);

        return back()->with('success', 'PDF Raporu başarıyla oluşturuldu.');
    }

    public function download(Project $project, Report $report)
    {
        Gate::authorize('view', $project);

        if (!Storage::disk('local')->exists($report->file_path)) {
            abort(404, 'Rapor dosyası sunucuda bulunamadı.');
        }

        AuditLog::log('report.downloaded', 'Report', $report->id);

        return Storage::disk('local')->download($report->file_path, "{$report->title}.pdf");
    }

    public function publicView(string $token)
    {
        $report = Report::where('public_token', $token)->firstOrFail();

        if (!$report->isPublicValid()) {
            abort(403, 'Bu rapor bağlantısının süresi dolmuş veya bağlantı iptal edilmiş.');
        }

        if (!Storage::disk('local')->exists($report->file_path)) {
            abort(404, 'Rapor dosyası bulunamadı.');
        }

        return Storage::disk('local')->response($report->file_path);
    }
}
