<?php

namespace App\Jobs;

use App\Services\CostOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CostOptimizationChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    /**
     * Backoff schedule (seconds) for retry attempts.
     * First retry after 30s, second after 60s.
     *
     * @var array<int>
     */
    public array $backoff = [30, 60];

    public function __construct(
        public string $category,
        public array $rows,
        public int $chunkIndex,
        public int $totalChunks,
        public int $userId,
    ) {
        $this->onQueue('optimization_chunks');
    }

    public function handle(CostOptimizationService $service): void
    {
        Log::info('CostOptimizationChunkJob: starting', [
            'category' => $this->category,
            'chunk_index' => $this->chunkIndex,
            'total_chunks' => $this->totalChunks,
            'rows' => count($this->rows),
        ]);

        $service->processCategoryChunk($this->category, $this->rows, $this->userId, $this->chunkIndex, $this->totalChunks);

        Log::info('CostOptimizationChunkJob: completed', [
            'category' => $this->category,
            'chunk_index' => $this->chunkIndex,
        ]);
    }
}
