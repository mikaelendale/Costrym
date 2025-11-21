<?php

namespace App\Jobs;

use App\Services\CostDecompositionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CostDecompositionChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    /**
     * @param  array<int,mixed>  $directCostsChunk
     */
    public function __construct(
        public array $directCostsChunk,
        public int $chunkIndex,
        public int $totalChunks,
        public int $userId,
    ) {
        $this->onQueue('cost_decomposer_chunks');
    }

    public function handle(CostDecompositionService $service): void
    {
        Log::info('CostDecompositionChunkJob: starting', [
            'chunk_index' => $this->chunkIndex,
            'total_chunks' => $this->totalChunks,
            'chunk_size' => count($this->directCostsChunk),
        ]);

        $service->processChunk($this->directCostsChunk, $this->chunkIndex, $this->totalChunks, $this->userId);

        Log::info('CostDecompositionChunkJob: completed', [
            'chunk_index' => $this->chunkIndex,
        ]);
    }
}
