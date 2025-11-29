# Stripe Cashier Integration Checklist

Quick setup checklist for adding Stripe subscriptions to Laravel projects.

---

## ✅ Phase 1: Installation (5 mins)

- [ ] Install Cashier: `composer require laravel/cashier`
- [ ] Publish migrations: `php artisan vendor:publish --tag="cashier-migrations"`
- [ ] Run migrations: `php artisan migrate`
- [ ] Add `Billable` trait to User model
- [ ] Add Stripe keys to `.env`:
  ```env
  STRIPE_KEY=pk_test_...
  STRIPE_SECRET=sk_test_...
  STRIPE_WEBHOOK_SECRET=whsec_...
  STRIPE_PRICE_STARTER_MONTHLY=price_...
  ```
- [ ] Disable CSRF for `stripe/*` in `bootstrap/app.php`

---

## ✅ Phase 2: Stripe Dashboard (5 mins)

- [ ] Create Product at https://dashboard.stripe.com/test/products
- [ ] Copy **Price ID** (starts with `price_...`, NOT `prod_...`)
- [ ] Start ngrok: `ngrok http 8000`
- [ ] Create webhook: `php artisan cashier:webhook --url "https://YOUR_NGROK_URL/stripe/webhook"`
- [ ] Get webhook secret from https://dashboard.stripe.com/test/webhooks
- [ ] Add webhook secret to `.env`

---

## ✅ Phase 3: Backend Routes (10 mins)

Add to `routes/web.php`:

- [ ] Dashboard route with subscription data
- [ ] `/subscription-checkout` route
- [ ] `/subscription-success` route
- [ ] `/subscription-cancel` route
- [ ] `/billing` route (portal)

---

## ✅ Phase 4: Middleware (5 mins)

- [ ] Create: `php artisan make:middleware Subscribed`
- [ ] Add redirect logic to middleware
- [ ] Register in `bootstrap/app.php`

---

## ✅ Phase 5: Frontend Components (15 mins)

- [ ] Generate routes: `php artisan wayfinder:generate`
- [ ] Update dashboard component
- [ ] Create `subscription/success.tsx`
- [ ] Create `subscription/cancel.tsx`

---

## ✅ Phase 6: Testing (10 mins)

- [ ] Start server: `php artisan serve`
- [ ] Ensure ngrok is running
- [ ] Visit `/subscription-checkout`
- [ ] Use test card: `4242 4242 4242 4242`
- [ ] Complete checkout
- [ ] Verify redirect to success page
- [ ] Check dashboard shows "Premium"
- [ ] Verify subscription in Stripe dashboard
- [ ] Test billing portal at `/billing`

---

## 🚀 Total Time: ~50 minutes

---

## Common Issues & Fixes

### ❌ "No such price" error
- **Fix:** Use Price ID (`price_...`) not Product ID (`prod_...`)

### ❌ Webhook not receiving events
- **Fix:** Check ngrok is running and webhook secret is correct

### ❌ Subscription not showing after payment
- **Fix:** Verify webhook secret in `.env` and check webhook logs in Stripe

### ❌ ngrok URL changed
- **Fix:** Restart webhook with new URL: `php artisan cashier:webhook --url "NEW_URL/stripe/webhook"`

---

## Production Checklist

- [ ] Switch to live Stripe keys (remove `_test_`)
- [ ] Create live products in Stripe dashboard
- [ ] Update `.env` with live price IDs
- [ ] Create live webhook with production URL
- [ ] Update `STRIPE_WEBHOOK_SECRET` with live secret
- [ ] Test with real card in live mode
- [ ] Monitor webhooks in Stripe dashboard

---

## Files to Copy to New Project

### Backend Files:
```
app/Http/Middleware/Subscribed.php
routes/web.php (subscription routes only)
```

### Frontend Files:
```
resources/js/pages/subscription/success.tsx
resources/js/pages/subscription/cancel.tsx
resources/js/pages/dashboard.tsx (subscription card section)
```

### Config:
```
.env (Stripe variables)
bootstrap/app.php (CSRF exception and middleware alias)
app/Models/User.php (Billable trait)
```

---

## Environment Variables Template

```env
# Stripe Configuration
STRIPE_KEY=pk_test_YOUR_KEY
STRIPE_SECRET=sk_test_YOUR_SECRET
STRIPE_WEBHOOK_SECRET=whsec_YOUR_SECRET
CASHIER_CURRENCY=usd

# Subscription Prices
STRIPE_PRICE_STARTER_MONTHLY=price_YOUR_PRICE_ID
STRIPE_PRICE_STARTER_YEARLY=price_YOUR_PRICE_ID
STRIPE_PRICE_PRO_MONTHLY=price_YOUR_PRICE_ID
```

---

## Quick Commands

```bash
# Setup
composer require laravel/cashier
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate

# Webhooks (local)
ngrok http 8000
php artisan cashier:webhook --url "NGROK_URL/stripe/webhook"

# Webhooks (production)
php artisan cashier:webhook

# Generate routes
php artisan wayfinder:generate

# Testing
php artisan serve
```

---

**Last Updated:** 2025-11-29
