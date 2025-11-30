<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DataIngestionStatusUpdated
{
    use Dispatchable;

    public int $userId;
    public string $status;
    public array $data;

    /**
     * Create a new event instance.
     *
     * @param int $userId
     * @param string $status - 'started', 'processing', 'categorizing', 'completed', 'failed'
     * @param array $data - Additional data like total_records, categorized_count, etc.
     */
    public function __construct(int $userId, string $status, array $data = [])
    {
        $this->userId = $userId;
        $this->status = $status;
        $this->data = $data;
    }


}
