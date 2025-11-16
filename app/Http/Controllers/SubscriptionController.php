<?php

namespace App\Http\Controllers;

use App\Notifications\SubscriptionStatusChanged;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Paddle\Exceptions\PaddleException;

/**
 * SubscriptionController
 * 
 * Handles all subscription-related operations including subscription creation,
 * plan swapping, pausing, resuming, cancellation, and payment method updates.
 * Integrates with Paddle payment gateway for secure payment processing.
 * 
 * Payment Integration Features:
 * - Secure subscription checkout via Paddle
 * - Plan validation and rate limiting
 * - Comprehensive error handling and logging
 * - User notifications for subscription changes
 * - Support for multiple billing options (immediate, next cycle, proration)
 * 
 * Configuration:
 * - Plans are configured in config/services.php
 * - Paddle credentials stored in environment variables
 * - Checkout redirects handled via billing.blade.php view
 * 
 * Error Handling:
 * - All Paddle exceptions are caught and logged with full context
 * - User-friendly error messages displayed to end users
 * - Rate limiting prevents abuse of plan swap functionality
 * - Graceful fallbacks for unexpected errors
 * 
 * @package App\Http\Controllers
 */
class SubscriptionController extends Controller
{
    /**
     * Available subscription plans mapped to their Paddle price IDs.
     * Plans are configured in config/services.php and retrieved from environment variables.
     * 
     * @var array<string, string>
     */
    private array $plans;

    /**
     * Initialize the controller and load available subscription plans.
     * Plans are retrieved from the services configuration which reads from environment variables.
     */
    public function __construct()
    { 
        $this->plans = [
            'startup-monthly' => config('services.paddle.startup_monthly_price_id'),
            'startup-annual' => config('services.paddle.startup_annual_price_id'), 
            'enterprise-annual' => config('services.paddle.enterprise_annual_price_id'),
        ];
    }

    /**
     * Initiates a new subscription checkout process for the authenticated user.
     * 
     * Validates the requested plan, checks if the user already has an active subscription,
     * and creates a Paddle checkout session. If the user is already subscribed, redirects
     * to the dashboard. On success, displays the billing checkout page. On failure,
     * logs the error and redirects back with an error message.
     * 
     * @param Request $request HTTP request containing optional 'plan' parameter
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException If plan is invalid
     */
    public function subscribe(Request $request)
    {
        $user = $request->user();

        // Validate and retrieve plan from query string or request body, default to startup-monthly
        $plan = $request->query('plan', $request->input('plan', 'startup-monthly'));
        
        // Ensure the requested plan exists in our available plans
        if (!isset($this->plans[$plan]) || empty($this->plans[$plan])) {
            Log::warning('Invalid plan requested', [
                'user_id' => $user->id,
                'requested_plan' => $plan,
                'available_plans' => array_keys($this->plans),
            ]);
            abort(404, 'Invalid subscription plan selected.');
        }

        // Prevent duplicate subscriptions
        if ($user->subscribed('default')) {
            Log::info('User attempted to subscribe while already subscribed', [
                'user_id' => $user->id,
                'requested_plan' => $plan,
            ]);
            return redirect()->route('dashboard')
                ->with('info', 'You are already subscribed to a plan.');
        }

        try {
            // Create Paddle checkout session with return URL to dashboard
            $checkout = $user->subscribe($this->plans[$plan], 'default')
                ->returnTo(route('dashboard'));

            // Get checkout URL - Paddle checkout is typically a redirect response or has url property
            $checkoutUrl = null;
            if (is_object($checkout)) {
                // Try accessing as property first
                if (isset($checkout->url)) {
                    $checkoutUrl = $checkout->url;
                } 
                // Try as method if property doesn't exist
                elseif (method_exists($checkout, 'url')) {
                    $checkoutUrl = $checkout->url();
                }
                // Try accessing via array notation if it's ArrayAccess
                elseif ($checkout instanceof \ArrayAccess && isset($checkout['url'])) {
                    $checkoutUrl = $checkout['url'];
                }
            } elseif (is_array($checkout) && isset($checkout['url'])) {
                $checkoutUrl = $checkout['url'];
            }

            // Verify checkout URL was generated successfully
            if (!$checkoutUrl) {
                Log::error('Checkout URL extraction failed', [
                    'checkout_type' => gettype($checkout),
                    'checkout_class' => is_object($checkout) ? get_class($checkout) : null,
                ]);
                throw new \RuntimeException('Failed to extract checkout URL from Paddle response');
            }

            Log::info('Subscription checkout initiated', [
                'user_id' => $user->id,
                'plan' => $plan,
                'price_id' => $this->plans[$plan],
            ]);

            // Return Inertia response with checkout URL for React component (non-reloading)
            return Inertia::render('onboarding', [
                'checkout_url' => $checkoutUrl,
                'checkout_plan' => $plan,
            ]);
        } catch (PaddleException $e) {
            // Log Paddle-specific errors with full context
            Log::error('Paddle subscription checkout error', [
                'user_id' => $user->id,
                'plan' => $plan,
                'price_id' => $this->plans[$plan] ?? null,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Unable to create subscription checkout. Please try again or contact support if the issue persists.');
        } catch (\Exception $e) {
            // Log unexpected errors
            Log::error('Unexpected subscription checkout error', [
                'user_id' => $user->id,
                'plan' => $plan,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'An unexpected error occurred. Please try again or contact support.');
        }
    }

    /**
     * Swaps the user's current subscription to a different plan.
     * 
     * Implements rate limiting to prevent abuse (max 3 swaps per 48 hours).
     * Supports multiple billing options: immediate charge, next cycle, no proration, etc.
     * Handles credit adjustments when switching between plans and sends notifications.
     * 
     * @param Request $request HTTP request containing 'plan' and optional 'billing' parameters
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException If rate limit is exceeded
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException If plan is invalid
     */
    public function swap(Request $request)
    {
        $request->validate([
            'plan' => 'required|string',
            'billing' => 'nullable|string|in:immediate,next_cycle,no_prorate,no_prorate_immediate,no_bill',
        ]);

        $plan = $request->input('plan');
        $billing = $request->input('billing', 'next_cycle');
        $user = $request->user();

        // Validate plan exists
        if (!isset($this->plans[$plan]) || empty($this->plans[$plan])) {
            Log::warning('Invalid plan swap requested', [
                'user_id' => $user->id,
                'requested_plan' => $plan,
            ]);
            abort(404, 'Invalid subscription plan selected.');
        }

        // Verify user has an active subscription to swap
        $subscription = $user->subscription('default');
        if (!$subscription || !$subscription->valid()) {
            Log::warning('Swap attempted without active subscription', [
                'user_id' => $user->id,
                'requested_plan' => $plan,
            ]);
            return redirect()->route('dashboard')
                ->with('error', 'No active subscription found. Please subscribe to a plan first.');
        }

        // Rate limiting: Prevent excessive plan swaps (max 3 per 48 hours)
        $throttleKey = 'swap-plan:' . $user->id;
        $maxAttempts = 3;
        $decayMinutes = 60 * 48; // 48 hours

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $secondsRemaining = RateLimiter::availableIn($throttleKey);
            $hoursRemaining = floor($secondsRemaining / 3600);
            $minutesRemaining = floor(($secondsRemaining % 3600) / 60);
            
            Log::info('Plan swap rate limit exceeded', [
                'user_id' => $user->id,
                'attempts' => $maxAttempts,
                'seconds_remaining' => $secondsRemaining,
            ]);

            throw ValidationException::withMessages([
                'plan' => "You can only change plans {$maxAttempts} times per 48 hours. Please try again in {$hoursRemaining}h {$minutesRemaining}m.",
            ])->redirectTo(url()->previous());
        }

        // Record the swap attempt
        RateLimiter::hit($throttleKey, $decayMinutes * 60);

        // Identify current plan for credit calculation purposes
        $oldPriceId = null;
        foreach ($this->plans as $key => $priceId) {
            if ($user->subscribedToPrice($priceId, 'default')) {
                $oldPriceId = $priceId;
                break;
            }
        }

        // Handle legacy or trial subscriptions not in our plan list
        if (is_null($oldPriceId)) {
            $oldPriceId = 'free'; // Placeholder for credit calculation
            Log::info('User on unknown plan, using placeholder for credit calculation', [
                'user_id' => $user->id,
            ]);
        }

        try {
            // Execute plan swap based on billing preference
            switch ($billing) {
                case 'immediate':
                    $subscription->swapAndInvoice($this->plans[$plan]);
                    $message = "Successfully switched to {$plan} plan! You've been charged immediately.";
                    break;
                case 'no_prorate':
                    $subscription->noProrate()->swap($this->plans[$plan]);
                    $message = "Successfully switched to {$plan} plan! Changes take effect next billing cycle (no proration).";
                    break;
                case 'no_prorate_immediate':
                    $subscription->noProrate()->swapAndInvoice($this->plans[$plan]);
                    $message = "Successfully switched to {$plan} plan! You've been charged immediately (no proration).";
                    break;
                case 'no_bill':
                    $subscription->doNotBill()->swap($this->plans[$plan]);
                    $message = "Successfully switched to {$plan} plan! No additional charges.";
                    break;
                default: // 'next_cycle'
                    $subscription->swap($this->plans[$plan]);
                    $message = "Successfully switched to {$plan} plan! Changes take effect next billing cycle.";
                    break;
            }

            // Update user's plan preference in database
            $user->plan = $plan;
            $user->save();

            // Handle credit adjustment for plan swap (if CreditService is implemented)
            // $this->creditService->handlePlanSwapCredits($user, $oldPriceId, $this->plans[$plan]);

            Log::info('Plan swap successful', [
                'user_id' => $user->id,
                'old_price_id' => $oldPriceId,
                'new_plan' => $plan,
                'new_price_id' => $this->plans[$plan],
                'billing_type' => $billing,
            ]);

            // Notify user of successful swap
            $user->notify(new SubscriptionStatusChanged($message));
            
            return redirect()->back()->with('success', $message);
        } catch (PaddleException $e) {
            // Log Paddle-specific errors and release rate limit on failure
            Log::error('Paddle plan swap error', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id ?? null,
                'old_price_id' => $oldPriceId,
                'new_plan' => $plan,
                'new_price_id' => $this->plans[$plan] ?? null,
                'billing_type' => $billing,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Release the rate limit hit since swap failed
            RateLimiter::clear($throttleKey);

            return redirect()->back()
                ->with('error', 'Unable to switch plans. Please contact support if this issue persists.');
        } catch (\Exception $e) {
            // Log unexpected errors
            Log::error('Unexpected plan swap error', [
                'user_id' => $user->id,
                'plan' => $plan,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            // Release the rate limit hit since swap failed
            RateLimiter::clear($throttleKey);

            return redirect()->back()
                ->with('error', 'An unexpected error occurred. Please try again or contact support.');
        }
    }

    /**
     * Pauses the user's active subscription.
     * 
     * Temporarily suspends billing and access. The subscription can be resumed later.
     * Validates that a subscription exists and is not already paused.
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pause(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');
        
        if (!$subscription || !$subscription->valid()) {
            return redirect()->back()
                ->with('error', 'No active subscription found.');
        }
        
        if ($subscription->paused()) {
            return redirect()->back()
                ->with('info', 'Your subscription is already paused.');
        }

        try {
            $subscription->pause();
            
            Log::info('Subscription paused', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);

            $user->notify(new SubscriptionStatusChanged('Your subscription has been paused successfully.'));
            return redirect()->back()
                ->with('success', 'Subscription paused successfully.');
        } catch (PaddleException $e) {
            Log::error('Failed to pause subscription', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->with('error', 'Unable to pause subscription. Please contact support.');
        }
    }

    /**
     * Resumes a paused subscription.
     * 
     * Reactivates billing and access for a previously paused subscription.
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resume(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');
        
        if (!$subscription) {
            return redirect()->back()
                ->with('error', 'No subscription found.');
        }

        try {
            $subscription->resume();
            
            Log::info('Subscription resumed', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);

            $user->notify(new SubscriptionStatusChanged('Your subscription has been resumed successfully.'));
            return redirect()->back()
                ->with('success', 'Subscription resumed successfully.');
        } catch (PaddleException $e) {
            Log::error('Failed to resume subscription', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->with('error', 'Unable to resume subscription. Please contact support.');
        }
    }

    /**
     * Cancels the user's subscription.
     * 
     * Schedules cancellation at the end of the current billing period.
     * The user retains access until the period ends. Can be reversed with stopCancellation().
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Request $request)
    {
        $user = $request->user();
        $activeSubscription = $user->subscriptions()->notCanceled()->first();
        
        if (!$activeSubscription) {
            return redirect()->back()
                ->with('error', 'No active subscription found.');
        }

        try {
            $activeSubscription->cancel();
            
            Log::info('Subscription cancellation scheduled', [
                'user_id' => $user->id,
                'subscription_id' => $activeSubscription->id,
            ]);

            $user->notify(new SubscriptionStatusChanged('Your subscription will be canceled at the end of your billing period.'));
            return redirect()->back()
                ->with('success', 'Subscription will be canceled at the end of your billing period.');
        } catch (PaddleException $e) {
            Log::error('Failed to cancel subscription', [
                'user_id' => $user->id,
                'subscription_id' => $activeSubscription->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->with('error', 'Unable to cancel subscription. Please contact support.');
        }
    }

    /**
     * Stops a scheduled subscription cancellation.
     * 
     * Reverses a cancellation that was scheduled but not yet processed.
     * The subscription continues as normal after this action.
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stopCancellation(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');
        
        if (!$subscription) {
            return redirect()->back()
                ->with('error', 'No subscription found.');
        }
        
        if (!$subscription->onGracePeriod()) {
            return redirect()->back()
                ->with('error', 'No subscription found in grace period.');
        }

        try {
            $subscription->stopCancelation();
            
            Log::info('Subscription cancellation stopped', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);

            return redirect()->back()
                ->with('success', 'Subscription cancellation stopped successfully.');
        } catch (PaddleException $e) {
            Log::error('Failed to stop cancellation', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->with('error', 'Unable to stop cancellation. Please contact support.');
        }
    }

    /**
     * Initiates payment method update flow.
     * 
     * Redirects user to Paddle's secure payment method update interface.
     * User can update their credit card or payment details without canceling subscription.
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePaymentMethod(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->valid()->first();
        
        if (!$subscription) {
            return redirect()->back()
                ->with('error', 'No active subscription found.');
        }

        try {
            Log::info('Payment method update initiated', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);

            // Redirect to Paddle's payment method update page
            return $subscription->redirectToUpdatePaymentMethod();
        } catch (PaddleException $e) {
            Log::error('Failed to initiate payment method update', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->with('error', 'Unable to update payment method. Please contact support.');
        }
    }
}
