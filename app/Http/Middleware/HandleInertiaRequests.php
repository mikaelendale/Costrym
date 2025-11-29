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
            
            // ============================================================================
            // SUBSCRIPTION DATA (Stripe)
            // ============================================================================
            'subscription' => fn () => $request->user() ? [
                'states' => [
                    'subscribed' => $request->user()->subscribed('default'),
                    'onTrial' => $request->user()->onTrial('default'),
                    'onGracePeriod' => $request->user()->subscription('default')?->onGracePeriod() ?? false,
                    'active' => $request->user()->subscription('default')?->active() ?? false,
                ],
                'defaultSubscription' => $request->user()->subscription('default'),
            ] : null,

            'price' => [
                'startup_monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
                'startup_annual' => env('STRIPE_PRICE_STARTER_ANNUAL'),
                'enterprise_annual' => env('STRIPE_PRICE_ENTERPRISE_ANNUAL')
            ],
            
            'customer' => fn () => $request->user() ? [
                'plan' => $request->user()->plan ?? 'free',
            ] : null,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
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
        ];
    }
}
