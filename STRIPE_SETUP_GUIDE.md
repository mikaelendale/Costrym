# Laravel Cashier (Stripe) Integration Guide

Complete step-by-step guide for integrating Stripe subscriptions into any Laravel project.

---

## Prerequisites

- Laravel 11+ project
- Stripe account (test mode)
- Composer installed

---

## Part 1: Installation & Configuration

### Step 1: Install Cashier

```bash
composer require laravel/cashier
```

### Step 2: Publish Migrations

```bash
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate
```

This creates:
- `subscriptions` table
- `subscription_items` table
- Adds columns to `users` table: `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`

### Step 3: Add Billable Trait to User Model

**File:** `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
    
}
```

### Step 4: Configure Environment Variables

**File:** `.env`

```env
# Stripe API Keys (from https://dashboard.stripe.com/test/apikeys)
STRIPE_KEY=pk_test_YOUR_PUBLISHABLE_KEY
STRIPE_SECRET=sk_test_YOUR_SECRET_KEY
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET

# Optional: Configure currency
CASHIER_CURRENCY=usd

# Your Stripe Price IDs (from Products page)
STRIPE_PRICE_STARTER_MONTHLY=price_YOUR_PRICE_ID_HERE
```

### Step 5: Disable CSRF for Webhooks

**File:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    
    $middleware->validateCsrfTokens(except: [
        'stripe/*',  // Add this line
    ]);
})
```

---

## Part 2: Stripe Dashboard Setup

### Step 1: Create Product & Price

1. Go to: https://dashboard.stripe.com/test/products
2. Click **"+ Add product"**
3. Fill in:
   - **Product name:** e.g., "Starter Monthly"
   - **Price:** e.g., $9.99
   - **Billing period:** Recurring → Monthly
4. Click **"Save product"**
5. **IMPORTANT:** Copy the **Price ID** (starts with `price_...`, NOT `prod_...`)
6. Add to `.env`: `STRIPE_PRICE_STARTER_MONTHLY=price_1ABC...`

### Step 2: Setup Webhooks (Local Development with ngrok)

#### Option A: Using ngrok (Recommended for local)

1. **Install ngrok:** https://ngrok.com/download
2. **Start ngrok:**
   ```bash
   ngrok http 8000
   ```
3. **Copy the ngrok URL** (e.g., `https://abc123.ngrok-free.dev`)
4. **Create webhook:**
   ```bash
   php artisan cashier:webhook --url "https://YOUR_NGROK_URL/stripe/webhook"
   ```
5. **Get webhook secret:**
   - Go to: https://dashboard.stripe.com/test/webhooks
   - Click your webhook
   - Click "Reveal" on Signing secret
   - Copy `whsec_...` value
   - Add to `.env`: `STRIPE_WEBHOOK_SECRET=whsec_...`

#### Option B: Production/Staging with Public URL

```bash
php artisan cashier:webhook --url "https://yourdomain.com/stripe/webhook"
```

---

## Part 3: Create Routes

**File:** `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard with subscription status
    Route::get('dashboard', function (Request $request) {
        $user = $request->user();
        
        return Inertia::render('dashboard', [
            'subscription' => [
                'isSubscribed' => $user->subscribed('default'),
                'onTrial' => $user->onTrial('default'),
                'onGracePeriod' => $user->subscription('default')?->onGracePeriod() ?? false,
                'plan' => $user->subscription('default')?->stripe_price ?? null,
                'endsAt' => $user->subscription('default')?->ends_at?->toDateTimeString() ?? null,
            ],
        ]);
    })->name('dashboard');

    // Subscription Checkout
    Route::get('/subscription-checkout', function (Request $request) {
        return $request->user()
            ->newSubscription('default', env('STRIPE_PRICE_STARTER_MONTHLY'))
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('subscription.success'),
                'cancel_url' => route('subscription.cancel'),
            ]);
    })->name('subscription.checkout');

    // Subscription Success
    Route::get('/subscription-success', function (Request $request) {
        return Inertia::render('subscription/success', [
            'subscribed' => $request->user()->subscribed('default'),
        ]);
    })->name('subscription.success');

    // Subscription Cancelled
    Route::get('/subscription-cancel', function (Request $request) {
        return Inertia::render('subscription/cancel');
    })->name('subscription.cancel');

    // Billing Portal
    Route::get('/billing', function (Request $request) {
        return $request->user()->redirectToBillingPortal(route('dashboard'));
    })->name('billing');
});
```

---

## Part 4: Create Subscription Middleware (Optional)

Protect routes that require active subscription.

### Step 1: Create Middleware

```bash
php artisan make:middleware Subscribed
```

**File:** `app/Http/Middleware/Subscribed.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Subscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->subscribed('default')) {
            return redirect()->route('subscription.checkout');
        }

        return $next($request);
    }
}
```

### Step 2: Register Middleware

**File:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    
    $middleware->alias([
        'subscribed' => \App\Http\Middleware\Subscribed::class,
    ]);
})
```

### Step 3: Use Middleware

```php
Route::get('/premium-feature', function () {
    // Only accessible to subscribed users
})->middleware('subscribed');
```

---

## Part 5: Frontend Components (React/Inertia)

### Generate Routes (if using Wayfinder)

```bash
php artisan wayfinder:generate
```

### Dashboard Component

**File:** `resources/js/pages/dashboard.tsx`

```tsx
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Link } from '@inertiajs/react';
import { checkout } from '@/routes/subscription';
import { billing } from '@/routes';
import { CreditCard, CheckCircle2, Clock, AlertCircle } from 'lucide-react';

interface DashboardProps {
    subscription: {
        isSubscribed: boolean;
        onTrial: boolean;
        onGracePeriod: boolean;
        plan: string | null;
        endsAt: string | null;
    };
}

export default function Dashboard({ subscription }: DashboardProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <CreditCard className="h-5 w-5" />
                    Subscription Status
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex items-center justify-between">
                    <div className="space-y-2">
                        <div className="flex items-center gap-2">
                            <p className="text-sm font-medium">Current Plan</p>
                            {subscription.isSubscribed && (
                                <Badge variant="default">
                                    <CheckCircle2 className="h-3 w-3 mr-1" />
                                    Active
                                </Badge>
                            )}
                            {subscription.onTrial && (
                                <Badge variant="secondary">
                                    <Clock className="h-3 w-3 mr-1" />
                                    Trial
                                </Badge>
                            )}
                        </div>
                        <p className="text-2xl font-bold">
                            {subscription.isSubscribed ? 'Premium' : 'Free'}
                        </p>
                    </div>
                    <div>
                        {!subscription.isSubscribed ? (
                            <Button asChild>
                                <Link href={checkout()}>Subscribe Now</Link>
                            </Button>
                        ) : (
                            <Button asChild variant="outline">
                                <Link href={billing()}>Manage Subscription</Link>
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
```

### Success Page

**File:** `resources/js/pages/subscription/success.tsx`

```tsx
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2 } from 'lucide-react';
import { dashboard, billing } from '@/routes';

interface SubscriptionSuccessProps {
    subscribed: boolean;
}

export default function SubscriptionSuccess({ subscribed }: SubscriptionSuccessProps) {
    return (
        <div className="flex min-h-screen items-center justify-center p-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <CheckCircle2 className="h-10 w-10 text-green-600" />
                    </div>
                    <CardTitle>Subscription Successful!</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-center text-sm text-muted-foreground">
                        {subscribed
                            ? 'Your subscription is now active.'
                            : 'Processing... You will receive confirmation shortly.'}
                    </p>
                    <div className="flex flex-col gap-2">
                        <Button asChild className="w-full">
                            <Link href={dashboard()}>Go to Dashboard</Link>
                        </Button>
                        <Button asChild variant="outline" className="w-full">
                            <Link href={billing()}>Manage Subscription</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
```

### Cancel Page

**File:** `resources/js/pages/subscription/cancel.tsx`

```tsx
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { XCircle } from 'lucide-react';
import { dashboard } from '@/routes';
import { checkout } from '@/routes/subscription';

export default function SubscriptionCancel() {
    return (
        <div className="flex min-h-screen items-center justify-center p-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100">
                        <XCircle className="h-10 w-10 text-yellow-600" />
                    </div>
                    <CardTitle>Subscription Cancelled</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-center text-sm text-muted-foreground">
                        No charges were made. You can try again anytime.
                    </p>
                    <div className="flex flex-col gap-2">
                        <Button asChild className="w-full">
                            <Link href={checkout()}>Try Again</Link>
                        </Button>
                        <Button asChild variant="outline" className="w-full">
                            <Link href={dashboard()}>Go to Dashboard</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
```

---

## Part 6: Common Usage Patterns

### Check Subscription Status

```php
// Check if user is subscribed
if ($user->subscribed('default')) {
    // User has active subscription
}

// Check if on trial
if ($user->onTrial('default')) {
    // User is on trial
}

// Check if cancelled but still active (grace period)
if ($user->subscription('default')->onGracePeriod()) {
    // Subscription cancelled but still active until end date
}

// Get subscription details
$subscription = $user->subscription('default');
$subscription->stripe_price; // Price ID
$subscription->ends_at; // End/renewal date
```

### Create Subscription with Trial

```php
$user->newSubscription('default', 'price_monthly')
    ->trialDays(7)
    ->checkout([
        'success_url' => route('success'),
        'cancel_url' => route('cancel'),
    ]);
```

### Add Multiple Prices

```php
$user->newSubscription('default', ['price_monthly', 'price_addon'])
    ->checkout([
        'success_url' => route('success'),
        'cancel_url' => route('cancel'),
    ]);
```

### Cancel Subscription

```php
// Cancel at end of billing period
$user->subscription('default')->cancel();

// Cancel immediately
$user->subscription('default')->cancelNow();
```

### Resume Cancelled Subscription

```php
// Only works during grace period
$user->subscription('default')->resume();
```

---

## Part 7: Testing Workflow

### 1. Start Your Server

```bash
php artisan serve
```

### 2. Start ngrok

```bash
ngrok http 8000
```

### 3. Test the Flow

1. Login to your app
2. Visit `/subscription-checkout`
3. Use Stripe test card: `4242 4242 4242 4242`
   - Any future expiry date
   - Any CVC
   - Any ZIP
4. Complete payment
5. Get redirected to success page
6. Check dashboard - should show "Premium" status
7. Visit `/billing` to manage subscription

### 4. Verify in Stripe Dashboard

- **Customers:** https://dashboard.stripe.com/test/customers
- **Subscriptions:** https://dashboard.stripe.com/test/subscriptions
- **Webhooks:** https://dashboard.stripe.com/test/webhooks

---

## Part 8: Troubleshooting

### Webhook Not Working

1. Check webhook secret in `.env`
2. Verify webhook URL in Stripe dashboard
3. Check ngrok is running
4. Look at webhook logs: https://dashboard.stripe.com/test/webhooks

### "No such price" Error

- Make sure you're using **Price ID** (`price_...`) not Product ID (`prod_...`)
- Verify price exists in test mode dashboard
- Check `.env` has correct price ID

### Subscription Not Showing

- Check database: `select * from subscriptions;`
- Verify webhook was received (check Stripe dashboard)
- Ensure `STRIPE_WEBHOOK_SECRET` is correct

### ngrok URL Changes

- ngrok free tier gives new URL each restart
- Re-run webhook command with new URL
- Or get static domain with paid ngrok

---

## Part 9: Production Deployment

### 1. Switch to Live Mode

```env
# Use live keys (no _test_ in them)
STRIPE_KEY=pk_live_YOUR_LIVE_KEY
STRIPE_SECRET=sk_live_YOUR_LIVE_SECRET
```

### 2. Create Live Products

- Switch dashboard to **Live mode**
- Create products/prices in live mode
- Update `.env` with live price IDs

### 3. Setup Production Webhook

```bash
php artisan cashier:webhook --url "https://yourdomain.com/stripe/webhook"
```

### 4. Update Webhook Secret

- Get secret from live webhook
- Update `STRIPE_WEBHOOK_SECRET` in production `.env`

---

## Quick Reference

### Artisan Commands

```bash
# Publish migrations
php artisan vendor:publish --tag="cashier-migrations"

# Run migrations
php artisan migrate

# Create webhook
php artisan cashier:webhook

# Generate Wayfinder routes
php artisan wayfinder:generate
```

### Stripe Dashboard URLs

- **Test Products:** https://dashboard.stripe.com/test/products
- **Test Webhooks:** https://dashboard.stripe.com/test/webhooks
- **Test API Keys:** https://dashboard.stripe.com/test/apikeys
- **Test Customers:** https://dashboard.stripe.com/test/customers

### Test Cards

- **Success:** `4242 4242 4242 4242`
- **3D Secure:** `4000 0025 0000 3155`
- **Decline:** `4000 0000 0000 9995`

---

## Resources

- **Laravel Cashier Docs:** https://laravel.com/docs/billing
- **Stripe Docs:** https://stripe.com/docs
- **Stripe CLI:** https://stripe.com/docs/stripe-cli
- **ngrok:** https://ngrok.com

---

**Created:** 2025-11-29  
**Laravel Version:** 12.x  
**Cashier Version:** 16.x  
**Tested with:** React, Inertia.js, TypeScript
