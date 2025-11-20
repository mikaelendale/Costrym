<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Subscription Status Updated Event
 *
 * Broadcasts subscription status changes to the user in real-time via Ably.
 * This allows the frontend to update subscription status without polling.
 *
 * Broadcasts to: private-user.{user_id}
 */
class SubscriptionStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user whose subscription status changed
     */
    public User $user;

    /**
     * Subscription status data
     */
    public array $subscriptionData;

    /**
     * Create a new event instance.
     *
     * @param  User  $user  The user whose subscription changed
     * @param  array  $subscriptionData  Current subscription status
     */
    public function __construct(User $user, array $subscriptionData = [])
    {
        $this->user = $user;
        $this->subscriptionData = $subscriptionData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('private-user.'.$this->user->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'subscription.status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'subscription' => $this->subscriptionData,
            'timestamp' => now()->toISOString(),
        ];
    }
}
