<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Cashier\Subscription;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get subscription data
        $defaultSubscription = $user->subscription('default');
        $hasSubscription = $defaultSubscription !== null;

        // Build subscription states
        $states = [
            'valid' => $hasSubscription && $defaultSubscription->valid(),
            'active' => $hasSubscription && $defaultSubscription->active(),
            'onTrial' => $hasSubscription && $defaultSubscription->onTrial(),
            'expiredTrial' => false,
            'notOnTrial' => !$hasSubscription || !$defaultSubscription->onTrial(),
            'recurring' => $hasSubscription && $defaultSubscription->recurring(),
            'pastDue' => $hasSubscription && $defaultSubscription->pastDue(),
            'paused' => false, // Stripe doesn't support pausing
            'notPaused' => true,
            'onPausedGracePeriod' => false,
            'notOnPausedGracePeriod' => true,
            'canceled' => $hasSubscription && $defaultSubscription->canceled(),
            'notCanceled' => !$hasSubscription || !$defaultSubscription->canceled(),
            'onGracePeriod' => $hasSubscription && $defaultSubscription->onGracePeriod(),
            'notOnGracePeriod' => !$hasSubscription || !$defaultSubscription->onGracePeriod(),
            'subscribed' => $user->subscribed('default'),
            'subscribedToDefault' => $user->subscribed('default'),
            'onGenericTrial' => $user->onGenericTrial(),
            'hasExpiredTrial' => false,
        ];

        // Get current plan info
        $currentPlan = 'Free';
        $subscriptionAmount = '$0/month';

        if ($hasSubscription) {
            $priceId = $defaultSubscription->stripe_price;
            
            $planMap = [
                env('STRIPE_PRICE_STARTER_MONTHLY') => 'startup-monthly',
                env('STRIPE_PRICE_STARTER_ANNUAL') => 'startup-annual',
                env('STRIPE_PRICE_ENTERPRISE_ANNUAL') => 'enterprise-annual',
            ];

            $currentPlan = $planMap[$priceId] ?? 'startup-monthly';

            $amountMap = [
                'startup-monthly' => '$' . env('STRIPE_PRICE_STARTER_MONTHLY'),
                'startup-annual' => '$' . env('STRIPE_PRICE_STARTER_ANNUAL'),
                'enterprise-annual' => '$' . env('STRIPE_PRICE_ENTERPRISE_ANNUAL'),
            ];

            $subscriptionAmount = $amountMap[$currentPlan] ?? '$0/month';
        }


        $prices = [
            'startup_monthly'  => '$' . env('STRIPE_PRICE_STARTER_MONTHLY'),
            'startup_annual' => '$' . env('STRIPE_PRICE_STARTER_ANNUAL'),
            'enterprise_annual' => '$' . env('STRIPE_PRICE_ENTERPRISE_ANNUAL'),
        ];
        return Inertia::render('user/billing-section', [
            'customer' => [
                'plan' => $currentPlan,
                'subscriptionAmount' => $subscriptionAmount,
            ],
            'price' => $prices,
            'subscription' => [
                'hasSubscription' => $hasSubscription,
                'defaultSubscription' => $defaultSubscription ? [
                    'id' => $defaultSubscription->id,
                    'type' => $defaultSubscription->type,
                    'paddle_id' => $defaultSubscription->stripe_id,
                    'status' => $defaultSubscription->stripe_status,
                    'trial_ends_at' => $defaultSubscription->trial_ends_at?->toDateTimeString(),
                    'ends_at' => $defaultSubscription->ends_at?->toDateTimeString(),
                    'paused_at' => null, // Stripe doesn't have paused_at like Paddle
                    'created_at' => $defaultSubscription->created_at->toDateTimeString(),
                    'updated_at' => $defaultSubscription->updated_at->toDateTimeString(),
                    'states' => array_filter($states, function ($key) {
                        return !in_array($key, ['subscribed', 'subscribedToDefault', 'onGenericTrial', 'hasExpiredTrial']);
                    }, ARRAY_FILTER_USE_KEY),
                ] : null,
                'states' => $states,
                'subscriptions' => $user->subscriptions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'type' => $sub->type,
                        'paddle_id' => $sub->stripe_id,
                        'status' => $sub->stripe_status,
                        'trial_ends_at' => $sub->trial_ends_at?->toDateTimeString(),
                        'ends_at' => $sub->ends_at?->toDateTimeString(),
                        'created_at' => $sub->created_at->toDateTimeString(),
                        'updated_at' => $sub->updated_at->toDateTimeString(),
                    ];
                })->toArray(),
                'trialEndsAt' => $defaultSubscription?->trial_ends_at?->toDateTimeString(),
            ],  
        ]);
    }

    public function cancelSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if ($subscription) {
            $subscription->cancel();
        }

        return redirect()->route('billing.index')->with('success', 'Subscription cancelled. You will retain access until the end of your billing period.');
    }

    public function resumeSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
        }

        return redirect()->route('billing.index')->with('success', 'Subscription resumed successfully.');
    }

    public function swapPlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|string',
            'billing' => 'required|in:immediate,next_cycle,no_prorate',
        ]);

        $user = $request->user();
        $subscription = $user->subscription('default');

        // Map plan IDs to Stripe price IDs
        $priceMap = [
            'startup-monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
            'startup-annual' => env('STRIPE_PRICE_STARTER_ANNUAL'),
            'enterprise-annual' => env('STRIPE_PRICE_ENTERPRISE_ANNUAL'),
        ];

        $newPriceId = $priceMap[$request->plan] ?? null;

        if (!$newPriceId) {
            return back()->withErrors(['plan' => 'Invalid plan selected.']);
        }

        if ($subscription) {
            switch ($request->billing) {
                case 'immediate':
                    // Swap immediately with proration
                    $subscription->swap($newPriceId);
                    break;
                case 'next_cycle':
                    // Swap at next billing cycle
                    $subscription->noProrate()->swap($newPriceId);
                    break;
                case 'no_prorate':
                    // Swap without proration
                    $subscription->noProrate()->swap($newPriceId);
                    break;
            }

            return redirect()->route('billing.index')->with('success', 'Plan changed successfully.');
        }

        return back()->withErrors(['subscription' => 'No active subscription found.']);
    }

    public function updatePaymentMethod(Request $request)
    {
        // Redirect to Stripe's billing portal for payment method update
        return $request->user()->redirectToBillingPortal(route('billing.index'));
    }

    public function downloadInvoice(Request $request, $invoiceId)
    {
        $user = $request->user();

        return $user->downloadInvoice($invoiceId, [
            'vendor' => config('app.name'),
            'product' => 'Subscription',
        ]);
    }
}
