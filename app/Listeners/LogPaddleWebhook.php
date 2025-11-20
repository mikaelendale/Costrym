<?php

namespace App\Listeners;

use App\Events\SubscriptionStatusUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Events\WebhookReceived;

/**
 * Paddle Webhook Event Listener
 *
 * Logs all incoming and processed Paddle webhook events for:
 * - Debugging subscription issues
 * - Monitoring payment events
 * - Tracking subscription status changes
 * - Auditing webhook processing
 * - Broadcasting subscription updates to users in real-time
 *
 * Webhook events include:
 * - transaction.created, transaction.completed
 * - subscription.created, subscription.updated, subscription.canceled
 * - payment.succeeded, payment.failed
 * - And other Paddle events
 */
class LogPaddleWebhook
{
    /**
     * Handle webhook received event.
     *
     * Logs when a webhook is first received from Paddle, before processing.
     * Useful for tracking all incoming webhook events and debugging delivery issues.
     *
     * @param  WebhookReceived  $event  Paddle webhook received event
     */
    public function handleReceived(WebhookReceived $event): void
    {
        $payload = $event->payload;

        Log::info('Paddle webhook received', [
            'event_type' => $payload['event_type'] ?? 'unknown',
            'event_id' => $payload['event_id'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? null,
            'data' => $payload['data'] ?? [],
        ]);
    }

    /**
     * Handle webhook handled event.
     *
     * Logs when a webhook has been successfully processed by Laravel Cashier.
     * Broadcasts subscription status updates to the user in real-time.
     * Useful for confirming webhook processing and tracking successful updates.
     *
     * @param  WebhookHandled  $event  Paddle webhook handled event
     */
    public function handleHandled(WebhookHandled $event): void
    {
        $payload = $event->payload;
        $eventType = $payload['event_type'] ?? 'unknown';

        Log::info('Paddle webhook handled successfully', [
            'event_type' => $eventType,
            'event_id' => $payload['event_id'] ?? null,
        ]);

        // Broadcast subscription status updates for relevant events
        $subscriptionEvents = [
            'subscription.created',
            'subscription.updated',
            'subscription.canceled',
            'subscription.past_due',
            'subscription.paused',
            'subscription.resumed',
            'transaction.completed',
            'transaction.payment_succeeded',
        ];

        if (in_array($eventType, $subscriptionEvents)) {
            $this->broadcastSubscriptionUpdate($payload);
        }
    }

    /**
     * Broadcast subscription status update to the user.
     *
     * Extracts user information from webhook payload and broadcasts
     * current subscription status for real-time frontend updates.
     *
     * @param  array  $payload  Webhook payload
     */
    private function broadcastSubscriptionUpdate(array $payload): void
    {
        try {
            $data = $payload['data'] ?? [];

            // Try to find user from customer ID or email
            $user = null;

            if (isset($data['customer_id'])) {
                $user = User::where('paddle_id', $data['customer_id'])->first();
            }

            if (! $user && isset($data['customer_email'])) {
                $user = User::where('email', $data['customer_email'])->first();
            }

            // Also check subscription data for customer info
            if (! $user && isset($data['subscription'])) {
                $subscriptionData = is_array($data['subscription']) ? $data['subscription'] : [];
                if (isset($subscriptionData['customer_id'])) {
                    $user = User::where('paddle_id', $subscriptionData['customer_id'])->first();
                }
            }

            if (! $user) {
                Log::warning('Could not find user for subscription broadcast', [
                    'event_type' => $payload['event_type'] ?? 'unknown',
                    'payload_data' => $data,
                ]);

                return;
            }

            // Get current subscription status
            $subscription = $user->subscription('default');
            $subscriptionData = [
                'has_subscription' => $user->subscribed('default'),
                'subscribed' => $user->subscribed('default'),
                'valid' => $subscription && $subscription->valid(),
                'active' => $subscription && $subscription->active(),
                'on_trial' => $subscription && $subscription->onTrial(),
                'recurring' => $subscription && $subscription->recurring(),
                'canceled' => $subscription && $subscription->canceled(),
                'on_grace_period' => $subscription && $subscription->onGracePeriod(),
            ];

            // Determine current plan
            $plans = [
                'startup-monthly' => config('services.paddle.startup_monthly_price_id'),
                'startup-annual' => config('services.paddle.startup_annual_price_id'),
                'enterprise-annual' => config('services.paddle.enterprise_annual_price_id'),
            ];

            $currentPlan = 'free';
            foreach ($plans as $planKey => $priceId) {
                if ($user->subscribedToPrice($priceId, 'default')) {
                    $currentPlan = $planKey;
                    break;
                }
            }

            $subscriptionData['current_plan'] = $currentPlan;

            // Broadcast the update
            event(new SubscriptionStatusUpdated($user, $subscriptionData));

            Log::info('Subscription status broadcasted', [
                'user_id' => $user->id,
                'event_type' => $payload['event_type'] ?? 'unknown',
                'current_plan' => $currentPlan,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast subscription update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
