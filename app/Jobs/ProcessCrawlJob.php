<?php

namespace App\Jobs;

use App\Models\Crawl;
use App\Modules\Crawler\CrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes
    public int $tries = 1;

    public function __construct(public Crawl $crawl)
    {
        $this->onQueue('crawls');
    }

    public function handle(CrawlService $crawlService): void
    {
        // Don't re-execute if already cancelled
        if (in_array($this->crawl->status, ['cancelled', 'completed'])) {
            return;
        }

        $crawlService->executeCrawl($this->crawl);
    }

    public function failed(?Throwable $exception): void
    {
        $this->crawl->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'Bilinmeyen kuyruk hatası',
            'completed_at' => now(),
        ]);
    }
}
