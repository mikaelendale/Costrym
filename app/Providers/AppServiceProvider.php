<?php

namespace App\Providers;

use App\Listeners\LogPaddleWebhook;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Events\WebhookReceived;

/**
 * Application Service Provider
 *
 * Registers application-wide services, event listeners, and configurations.
 *
 * Currently handles:
 * - Paddle webhook event logging for subscription management
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * Maps Paddle webhook events to their respective listeners for logging
     * and monitoring subscription-related events.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Log all received Paddle webhooks (before processing)
        WebhookReceived::class => [
            LogPaddleWebhook::class.'@handleReceived',
        ],
        // Log all handled Paddle webhooks (after processing)
        WebhookHandled::class => [
            LogPaddleWebhook::class.'@handleHandled',
        ],
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
