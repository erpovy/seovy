<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] ?? 'Teknik SEO Denetim Raporu' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #4f46e5;
            margin: 0 0 5px 0;
            font-size: 22px;
        }
        .meta-info {
            color: #64748b;
            font-size: 11px;
        }
        .stats-grid {
            margin-bottom: 25px;
            width: 100%;
        }
        .stat-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            display: inline-block;
            width: 22%;
            margin-right: 2%;
            box-sizing: border-box;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 5px;
        }
        .stat-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
        }
        .score-good { color: #16a34a; }
        .score-warn { color: #d97706; }
        .score-bad { color: #dc2626; }
        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 11px;
        }
        .badge-critical {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-notice {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $workspace->name }} — Teknik SEO Raporu</h1>
    <div class="meta-info">
        <strong>Proje:</strong> {{ $project->name }} ({{ $project->domain }}) &bull; 
        <strong>Tarama Tarihi:</strong> {{ $generated_at }} &bull; 
        <strong>Rapor ID:</strong> #{{ $crawl->id }}
    </div>
</div>

<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-label">Sağlık Skoru</div>
        <div class="stat-value {{ $stats['health_score'] >= 80 ? 'score-good' : ($stats['health_score'] >= 50 ? 'score-warn' : 'score-bad') }}">
            {{ $stats['health_score'] }}%
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Taranan Sayfa</div>
        <div class="stat-value">{{ $stats['total_pages'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Kritik Sorunlar</div>
        <div class="stat-value score-bad">{{ $stats['critical_count'] }}</div>
    </div>
    <div class="stat-box" style="margin-right: 0;">
        <div class="stat-label">Uyarılar</div>
        <div class="stat-value score-warn">{{ $stats['warning_count'] }}</div>
    </div>
</div>

<div class="section-title">En Kritik SEO Bulguları ve Eylem Planı</div>

<table>
    <thead>
        <tr>
            <th style="width: 15%;">Önem</th>
            <th style="width: 20%;">Kategori</th>
            <th style="width: 35%;">Sorun ve URL</th>
            <th style="width: 30%;">Çözüm Önerisi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($topFindings as $finding)
        <tr>
            <td>
                @if($finding->severity === 'critical')
                    <span class="badge-critical">KRİTİK</span>
                @elseif($finding->severity === 'warning')
                    <span class="badge-warning">UYARI</span>
                @else
                    <span class="badge-notice">ÖNERİ</span>
                @endif
            </td>
            <td><strong>{{ ucfirst($finding->category) }}</strong></td>
            <td>
                <strong>{{ $finding->title }}</strong><br>
                <small style="color: #64748b;">{{ $finding->page?->url ?? '-' }}</small>
            </td>
            <td>{{ $finding->recommendation }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" style="text-align: center; color: #64748b;">Bu taramada hiçbir kritik sorun tespit edilmedi. Harika!</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Seovy Teknik SEO Platformu tarafından üretilmiştir &bull; {{ date('Y') }}
</div>

</body>
</html>
