<?php

namespace App\Events;

use IlluminateBroadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataIngestionStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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
        return 'ingestion.status.updated';
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
