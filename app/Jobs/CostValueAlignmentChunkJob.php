<?php

namespace App\Jobs;

use App\Services\CostOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CostValueAlignmentChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    /**
     * Backoff schedule (seconds) for retry attempts.
     *
     * @var array<int>
     */
    public array $backoff = [30, 60];

    public function __construct(
        public string $category,
        public array $optimizerDataChunk,
        public int $chunkIndex,
        public int $totalChunks,
        public int $userId,
    ) {
        $this->onQueue('alignment_chunks');
    }

    public function handle(CostOptimizationService $service): void
    {
        Log::info('CostValueAlignmentChunkJob: starting', [
            'category' => $this->category,
            'chunk_index' => $this->chunkIndex,
            'total_chunks' => $this->totalChunks,
            'chunk_size' => count($this->optimizerDataChunk),
        ]);

        $service->processAlignmentChunk($this->category, $this->optimizerDataChunk, $this->userId, $this->chunkIndex, $this->totalChunks);

        Log::info('CostValueAlignmentChunkJob: completed', [
            'category' => $this->category,
            'chunk_index' => $this->chunkIndex,
        ]);
    }
}
