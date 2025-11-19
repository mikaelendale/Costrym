<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Paddle\Exceptions\PaddleException;

class BillingController extends Controller
{
    private array $plans;

    /**
     * Create a new controller instance.
     *
     * Initializes the available subscription plans with their details.
     *
     * @return void
     */
    public function __construct()
    {
        $this->plans = [
            'free' => [
                'name' => 'Free Plan',
                'paddle_id' => null,
                'price' => 0,
                'features' => ['Basic features'],
            ],
            'plus-monthly' => [
                'name' => 'plus Monthly',
                'paddle_id' => config('services.paddle.plus_monthly_price_id'),
                'price' => 29,
                'features' => ['Advanced features'],
            ],
            'plus-annual' => [
                'name' => 'plus Annual',
                'paddle_id' => config('services.paddle.plus_annual_price_id'),
                'price' => 290,
                'features' => ['Advanced features', '2 months free'],
            ],
        ];
    }

    /**
     * Display the billing dashboard.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get subscription data

        return Inertia::render('user/billing-section');
    }

    /**
     * Get the user's recent billing history.
     *
     * @param  mixed  $user  The user instance
     * @return array Formatted transaction history
     */
    private function getBillingHistory($user)
    {
        $transactions = $user->transactions()->latest()->take(10)->get();

        return $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'date' => $transaction->billed_at->format('M d, Y'),
                'amount' => $transaction->total() == 0 ? '$0.00' : '$'.number_format((float) $transaction->total() / 100, 2),
                'status' => ucfirst($transaction->paddle_status),
                'description' => $transaction->total() == 0 ? 'Trial Setup' : 'Subscription Payment',
            ];
        })->toArray();
    }

    /**
     * Get the user's current subscription plan.
     *
     * @param  mixed  $user  The user instance
     * @return string The current plan identifier
     */
    private function getCurrentPlan($user)
    {
        $activeSub = $user->subscription('default');

        if (! $activeSub) {
            return 'free';
        }

        foreach ($this->plans as $planId => $plan) {
            if ($plan['paddle_id'] === $activeSub->paddle_price_id) {
                return $planId;
            }
        }

        return 'free';
    }

    /**
     * Get plan name from Paddle price ID.
     *
     * @param  string  $paddleId  The Paddle price ID
     * @return string The plan name or 'Unknown'
     */
    private function getPlanNameFromPaddleId($paddleId)
    {
        foreach ($this->plans as $plan) {
            if ($plan['paddle_id'] === $paddleId) {
                return $plan['name'];
            }
        }

        return 'Unknown';
    }

    /**
     * Change the user's subscription plan.
     *
     * Handles both upgrading to paid plans and downgrading to free plan.
     * For free plan, cancels existing subscription. For paid plans, creates
     * new subscription or swaps existing one.
     *
     * @param  Request  $request  The incoming request
     * @return \Illuminate\Http\RedirectResponse|\Inertia\Response|\Illuminate\Contracts\View\View Redirects back, to checkout, or returns billing view
     */
    public function changePlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:'.implode(',', array_keys($this->plans)),
            'billing' => 'sometimes|string|in:immediate,no_prorate,next_cycle',
        ]);

        $planId = $request->input('plan');
        $billing = $request->input('billing', 'next_cycle');
        $user = $request->user();

        $plan = $this->plans[$planId];

        // Handle free plan
        if ($planId === 'free') {
            $subscription = $user->subscription('default');
            if ($subscription) {
                try {
                    $subscription->cancel();

                    return back()->with('success', 'Subscription canceled. You\'ll have access until the end of your billing period.');
                } catch (PaddleException $e) {
                    return back()->with('error', 'Unable to cancel subscription.');
                }
            }

            return back()->with('success', 'You\'re already on the free plan.');
        }

        // Handle paid plans
        $subscription = $user->subscription('default');

        if (! $subscription || ! $subscription->valid()) {
            // Create new subscription
            try {
                $checkout = $user->subscribe($plan['paddle_id'], 'default')
                    ->returnTo(route('billing.index'));

                return view('billing', ['checkout' => $checkout]);
            } catch (PaddleException $e) {
                return back()->with('error', 'Unable to create subscription.');
            }
        }

        // Swap existing subscription
        try {
            switch ($billing) {
                case 'immediate':
                    $subscription->swapAndInvoice($plan['paddle_id']);
                    $message = "Successfully switched to {$plan['name']} plan! You've been charged immediately.";
                    break;
                case 'no_prorate':
                    $subscription->noProrate()->swap($plan['paddle_id']);
                    $message = "Successfully switched to {$plan['name']} plan! Changes take effect next billing cycle.";
                    break;
                default:
                    $subscription->swap($plan['paddle_id']);
                    $message = "Successfully switched to {$plan['name']} plan! Changes take effect next billing cycle.";
                    break;
            }

            return back()->with('success', $message);
        } catch (PaddleException $e) {
            return back()->with('error', 'Unable to switch plans.');
        }
    }

    /**
     * Update the payment method for the user's subscription.
     *
     * Redirects the user to Paddle's secure payment method update page.
     *
     * @param  Request  $request  The incoming request
     * @return \Illuminate\Http\RedirectResponse|\Inertia\Response Redirects to Paddle or back with error
     */
    public function updatePaymentMethod(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (! $subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            return redirect($subscription->redirectToUpdatePaymentMethod());
        } catch (PaddleException $e) {
            return back()->with('error', 'Unable to update payment method.');
        }
    }

    /**
     * Cancel the user's active subscription.
     *
     * Schedules the subscription for cancellation at the end of the billing period.
     * User retains access until the subscription expires.
     *
     * @param  Request  $request  The incoming request
     * @return \Illuminate\Http\RedirectResponse Redirects back with success/error message
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (! $subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            $subscription->cancel();

            return back()->with('success', 'Subscription canceled. You\'ll have access until the end of your billing period.');
        } catch (PaddleException $e) {
            return back()->with('error', 'Unable to cancel subscription.');
        }
    }

    /**
     * Download an invoice PDF for a specific transaction.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $transactionId  The transaction ID
     * @return \Illuminate\Http\RedirectResponse Redirects to invoice PDF or back with error
     */
    public function downloadInvoice(Request $request, $transactionId)
    {
        $user = $request->user();
        $transaction = $user->transactions()->where('id', $transactionId)->first();

        if (! $transaction) {
            return back()->with('error', 'Invoice not found.');
        }

        try {
            return $transaction->redirectToInvoicePdf();
        } catch (PaddleException $e) {
            return back()->with('error', 'Unable to download invoice.');
        }
    }

    /**
     * Pause the user's active subscription.
     *
     * Temporarily suspends billing and service access.
     * Can be resumed later using resumeSubscription().
     *
     * @param  Request  $request  The incoming request
     * @return \Illuminate\Http\RedirectResponse Redirects back with success/error message
     */
    public function pauseSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (! $subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            $subscription->pause();

            return back()->with('success', 'Subscription paused successfully.');
        } catch (PaddleException $e) {
            return back()->with('error', 'Unable to pause subscription.');
        }
    }

    /**
     * Resume a paused or canceled subscription.
     *
     * Reactivates a paused subscription or stops a scheduled cancellation.
     * Restores normal billing and service access.
     *
     * @param  Request  $request  The incoming request
     * @return \Illuminate\Http\RedirectResponse Redirects back with success/error message
     */
    public function resumeSubscription(Request $request)
    {
        $user = $request->user();
        $pausedSubscription = $user->subscription('default');

        if (! $pausedSubscription) {
            $canceledSubscription = $user->subscription('default');

            if ($canceledSubscription) {
                try {
                    $canceledSubscription->resume();

                    return back()->with('success', 'Subscription resumed successfully.');
                } catch (PaddleException $e) {
                    return back()->with('error', 'Unable to resume subscription.');
                }
            }

            return back()->with('error', 'No paused or canceled subscription found to resume.');
        }

        try {
            $pausedSubscription->resume();

            return back()->with('success', 'Subscription resumed successfully.');
        } catch (PaddleException $e) {
            return back()->with('error', 'Unable to resume subscription.');
        }
    }

    /**
     * Swap the user's current subscription to a different plan.
     *
     * Changes the subscription plan with various billing options including
     * immediate billing, proration, and next-cycle changes.
     *
     * @param  Request  $request  The incoming request
     * @return \Illuminate\Http\RedirectResponse Redirects back with success/error message
     */
    public function swapPlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:'.implode(',', array_keys($this->plans)),
            'billing' => 'sometimes|string|in:immediate,no_prorate,no_prorate_immediate,no_bill,next_cycle',
        ]);

        $plan = $request->input('plan');
        $billing = $request->input('billing', 'next_cycle');
        $user = $request->user();

        $subscription = $user->subscription('default');

        if (! $subscription || ! $subscription->valid()) {
            return back()->with('error', 'No active subscription found to swap.');
        }

        try {
            switch ($billing) {
                case 'immediate':
                    $subscription->swapAndInvoice($this->plans[$plan]['paddle_id']);
                    $message = "Successfully switched to {$this->plans[$plan]['name']} plan! You've been charged immediately.";
                    break;

                case 'no_prorate':
                    $subscription->noProrate()->swap($this->plans[$plan]['paddle_id']);
                    $message = "Successfully switched to {$this->plans[$plan]['name']} plan! Changes take effect next billing cycle (no proration).";
                    break;

                case 'no_prorate_immediate':
                    $subscription->noProrate()->swapAndInvoice($this->plans[$plan]['paddle_id']);
                    $message = "Successfully switched to {$this->plans[$plan]['name']} plan! You've been charged immediately (no proration).";
                    break;

                case 'no_bill':
                    $subscription->doNotBill()->swap($this->plans[$plan]['paddle_id']);
                    $message = "Successfully switched to {$this->plans[$plan]['name']} plan! No additional charges.";
                    break;

                default:
                    $subscription->swap($this->plans[$plan]['paddle_id']);
                    $message = "Successfully switched to {$this->plans[$plan]['name']} plan! Changes take effect next billing cycle.";
                    break;
            }

            return back()->with('success', $message);

        } catch (PaddleException $e) {
            return back()->with('error', 'Unable to switch plans.');
        }
    }
}
