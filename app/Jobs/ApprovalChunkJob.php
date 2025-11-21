<?php

namespace App\Jobs;

use App\Services\AutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApprovalChunkJob implements ShouldQueue
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
        public array $plansChunk,
        public int $chunkIndex,
        public int $totalChunks,
        public int $userId,
    ) {
        $this->onQueue('approval_chunks');
    }

    public function handle(AutomationService $service): void
    {
        Log::info('ApprovalChunkJob: starting', [
            'category' => $this->category,
            'chunk_index' => $this->chunkIndex,
            'total_chunks' => $this->totalChunks,
            'chunk_size' => count($this->plansChunk),
        ]);

        $service->processApprovalChunk(
            $this->category,
            $this->plansChunk,
            $this->userId,
            $this->chunkIndex,
            $this->totalChunks,
        );

        Log::info('ApprovalChunkJob: completed', [
            'category' => $this->category,
            'chunk_index' => $this->chunkIndex,
        ]);
    }
}
