<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOnboardingStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is authenticated and hasn't completed onboarding
        if ($user && !$user->onboarding_status) {
            // Allow access to onboarding route to avoid redirect loops
            if (!$request->routeIs('onboarding')) {
                return redirect()->route('onboarding');
            }
        }

        return $next($request);
    }
}
