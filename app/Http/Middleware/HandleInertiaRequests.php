<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default with all Inertia pages.
     *
     * This method provides global data accessible to all React components:
     * - Authentication and authorization data
     * - Subscription status (automatically updated via webhooks)
     * - Paddle client-side token for checkout
     * - Navigation routes and sidebar state
     * - Flash messages and notifications
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        // ============================================================================
        // DETECT USER'S CURRENT SUBSCRIPTION PLAN
        // ============================================================================
        // Determines which plan the user is subscribed to by checking Paddle price IDs
        // This is used throughout the app to show plan-specific features and limits
        $plan = 'free';
        $subscriptionAmount = '$0';

        if ($user = Auth::user()) {
            if ($user->subscribedToPrice(config('services.paddle.startup_monthly_price_id'), 'default')) {
                $plan = 'startup-monthly';
                $subscriptionAmount = config('services.paddle.startup_monthly_amount', '$29.99');
            } elseif ($user->subscribedToPrice(config('services.paddle.startup_annual_price_id'), 'default')) {
                $plan = 'startup-annual';
                $subscriptionAmount = config('services.paddle.startup_annual_amount', '$500');
            } elseif ($user->subscribedToPrice(config('services.paddle.enterprise_annual_price_id'), 'default')) {
                $plan = 'enterprise-annual';
                $subscriptionAmount = config('services.paddle.enterprise_annual_amount', '$999');
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'url' => config('app.url'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'can' => $request->user()?->getAllPermissions()->pluck('name'),
                'roles' => $request->user()?->getRoleNames(),
                'notifications' => $request->user()?->notifications()->limit(3)->get(),
            ],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            // ============================================================================
            // PADDLE CONFIGURATION
            // ============================================================================
            // Client-side token for Paddle.js SDK initialization
            'paddle' => [
                'client_side_token' => config('services.paddle.client_side_token'),
            ],

            // ============================================================================
            // UI STATE
            // ============================================================================
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            // ============================================================================
            // NAVIGATION ROUTES
            // ============================================================================
            'routes' => fn (): array => array_filter([
                ['name' => 'Dashboard', 'url' => route('dashboard')],
                ['name' => 'Profile', 'url' => route('profile.edit')],
                ['name' => 'Notifications', 'url' => route('notifications')],
                $request->user()?->hasRole('admin') ? ['name' => 'User Management', 'url' => route('admin.users.index')] : null,
                $request->user()?->hasRole('admin') ? ['name' => 'Roles and Permissions', 'url' => route('admin.roles-permissions')] : null,
            ]),

            // ============================================================================
            // SUBSCRIPTION DATA (Laravel Cashier Paddle)
            // ============================================================================
            // Comprehensive subscription information automatically updated via webhooks
            // Accessible in all React components via usePage().props.subscription
            'subscription' => fn () => $this->getSubscriptionData($request),

            // ============================================================================
            // BILLING DATA
            // ============================================================================
            // Transaction history and billing information
            'billing' => fn () => $this->getBillingData($request),

            // ============================================================================
            // CUSTOMER PLAN INFORMATION
            // ============================================================================
            // Current plan and subscription amount for display purposes
            'customer' => [
                'plan' => $plan,
                'subscriptionAmount' => $subscriptionAmount,
            ],

            // ============================================================================
            // PRICING INFORMATION
            // ============================================================================
            // Plan pricing for display in UI (not used for checkout - prices come from Paddle)
            'price' => [
                'startup_monthly' => config('services.paddle.startup_monthly_amount', '$29.99'),
                'startup_annual' => config('services.paddle.startup_annual_amount', '$500'),
                'enterprise_annual' => config('services.paddle.enterprise_annual_amount', '$999'),
            ],
        ];
    }

    /**
     * Get comprehensive subscription data for the authenticated user.
     *
     * Returns detailed subscription information including:
     * - All subscription states (active, trial, grace period, etc.)
     * - Default subscription details
     * - All user subscriptions
     * - User-level subscription checks
     *
     * This data is automatically updated when Paddle webhooks are received,
     * ensuring the frontend always has current subscription status without
     * requiring separate API calls.
     *
     * @param  Request  $request  HTTP request
     * @return array Subscription data structure
     */
    private function getSubscriptionData(Request $request): array
    {
        if (! $request->user()) {
            return [
                'hasSubscription' => false,
                'states' => [],
                'subscriptions' => [],
            ];
        }

        $user = Auth::user();
        $defaultSubscription = $user->subscription('default');

        // Get all subscriptions for the user
        $allSubscriptions = $user->subscriptions()->get()->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'type' => $subscription->type,
                'paddle_id' => $subscription->paddle_id,
                'status' => $subscription->paddle_status,
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'ends_at' => $subscription->ends_at?->toISOString(),
                'paused_at' => $subscription->paused_at?->toISOString(),
                'created_at' => $subscription->created_at->toISOString(),
                'updated_at' => $subscription->updated_at->toISOString(),
                // Individual subscription states
                'states' => [
                    'valid' => $subscription->valid(),
                    'active' => $subscription->active(),
                    'onTrial' => $subscription->onTrial(),
                    'recurring' => $subscription->recurring(),
                    'pastDue' => $subscription->pastDue(),
                    'paused' => $subscription->paused(),
                    'onPausedGracePeriod' => $subscription->onPausedGracePeriod(),
                    'canceled' => $subscription->canceled(),
                    'onGracePeriod' => $subscription->onGracePeriod(),
                ],
            ];
        });

        // Default subscription states (most commonly used)
        $defaultStates = [];
        if ($defaultSubscription) {
            $defaultStates = [
                'valid' => $defaultSubscription->valid(),
                'active' => $defaultSubscription->active(),
                'onTrial' => $defaultSubscription->onTrial(),
                'expiredTrial' => $user->hasExpiredTrial(),
                'notOnTrial' => ! $defaultSubscription->onTrial(),
                'recurring' => $defaultSubscription->recurring(),
                'pastDue' => $defaultSubscription->pastDue(),
                'paused' => $defaultSubscription->paused(),
                'notPaused' => ! $defaultSubscription->paused(),
                'onPausedGracePeriod' => $defaultSubscription->onPausedGracePeriod(),
                'notOnPausedGracePeriod' => ! $defaultSubscription->onPausedGracePeriod(),
                'canceled' => $defaultSubscription->canceled(),
                'notCanceled' => ! $defaultSubscription->canceled(),
                'onGracePeriod' => $defaultSubscription->onGracePeriod(),
                'notOnGracePeriod' => ! $defaultSubscription->onGracePeriod(),
            ];
        }

        // User-level subscription checks
        $userStates = [
            'subscribed' => $user->subscribed(),
            'subscribedToDefault' => $user->subscribed('default'),
            'onGenericTrial' => $user->onGenericTrial(),
            'hasExpiredTrial' => $user->hasExpiredTrial(),
        ];

        return [
            'hasSubscription' => $user->subscribed(),
            'defaultSubscription' => $defaultSubscription ? [
                'id' => $defaultSubscription->id,
                'type' => $defaultSubscription->type,
                'paddle_id' => $defaultSubscription->paddle_id,
                'status' => $defaultSubscription->paddle_status,
                'trial_ends_at' => $defaultSubscription->trial_ends_at?->toISOString(),
                'ends_at' => $defaultSubscription->ends_at?->toISOString(),
                'paused_at' => $defaultSubscription->paused_at?->toISOString(),
            ] : null,
            'states' => array_merge($userStates, $defaultStates),
            'subscriptions' => $allSubscriptions,
            'trialEndsAt' => $user->trialEndsAt()?->toISOString(),
        ];
    }

    /**
     * Get billing and transaction data for the authenticated user.
     *
     * Returns recent transactions and billing information from Paddle.
     * Used for displaying payment history and invoices in the UI.
     *
     * @param  Request  $request  HTTP request
     * @return array Billing data structure with transactions
     */
    private function getBillingData(Request $request): array
    {
        if (! $request->user()) {
            return [
                'transactions' => [],
                'receipts' => [],
            ];
        }

        $user = $request->user();

        return [
            'transactions' => $user->transactions()->latest()->take(10)->get()->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'paddle_id' => $transaction->paddle_id,
                    'paddle_subscription_id' => $transaction->paddle_subscription_id,
                    'invoice_number' => $transaction->invoice_number,
                    'status' => $transaction->status,
                    'total' => $transaction->total,
                    'tax' => $transaction->tax,
                    'currency' => $transaction->currency,
                    'billed_at' => $transaction->billed_at?->toISOString(),
                    'created_at' => $transaction->created_at->toISOString(),
                ];
            }),
        ];
    }
}
