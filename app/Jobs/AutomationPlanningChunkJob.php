<?php

namespace App\Jobs;

use App\Services\AutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutomationPlanningChunkJob implements ShouldQueue
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
        public array $alignedDataChunk,
        public int $chunkIndex,
        public int $totalChunks,
        public int $userId,
    ) {
        $this->onQueue('automation_planning_chunks');
    }

    public function handle(AutomationService $service): void
    {
        Log::info('AutomationPlanningChunkJob: starting', [
            'category' => $this->category,
            'chunk_index' => $this->chunkIndex,
            'total_chunks' => $this->totalChunks,
            'chunk_size' => count($this->alignedDataChunk),
        ]);

        $service->processPlanningChunk(
            $this->category,
            $this->alignedDataChunk,
            $this->userId,
            $this->chunkIndex,
            $this->totalChunks,
        );

        Log::info('AutomationPlanningChunkJob: completed', [
            'category' => $this->category,
            'chunk_index' => $this->chunkIndex,
        ]);
    }
}
