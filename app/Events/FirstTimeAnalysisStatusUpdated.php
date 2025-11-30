<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FirstTimeAnalysisStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('user.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'analysis.status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'status' => $this->status,
            'data' => $this->data,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
