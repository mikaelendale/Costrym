<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class FirstTimeAnalysisStatusUpdated
{
    use Dispatchable;

    public int $userId;
    public string $status;
    public array $data;

    /**
     * Create a new event instance.
     *
     * @param int $userId
     * @param string $status - 'started', 'analyzing', 'complete', 'failed'
     * @param array $data - Additional data like current_step, total_steps, step_name, etc.
     */
    public function __construct(int $userId, string $status, array $data = [])
    {
        $this->userId = $userId;
        $this->status = $status;
        $this->data = $data;
    }


}
