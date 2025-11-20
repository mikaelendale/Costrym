<?php

namespace App\Jobs;

use App\Services\CategorizeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CategorizeChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // TODO: Add tries and exponential backoff
    public $tries = 1;

    public $backoff = 1;

    public function __construct(
        public string $title,
        public array $rows,
        public int $chunkIndex,
        public int $startRowNumber,
        public int $endRowNumber,
        public int $userId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CategorizeService $categorizeService): void
    {
        $payload = [
            'title' => $this->title,
            'chunk' => [
                'index' => $this->chunkIndex,
                'range' => [
                    'start' => $this->startRowNumber,
                    'end' => $this->endRowNumber,
                ],
                // Re-index the rows so each chunk has row 1..N semantics
                'rows' => array_values($this->rows),
            ],
        ];

        Log::info('CategorizeChunkJob: dispatching categorization', [
            'title' => $this->title,
            'chunk_index' => $this->chunkIndex,
            'range' => $this->startRowNumber.'-'.$this->endRowNumber,
            'rows_count' => count($this->rows),
        ]);

        // CategorizeService expects a JSON string input
        $categorizeService->categorize(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $this->userId
        );
    }
}
