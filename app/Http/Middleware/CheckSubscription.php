<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admins bypass subscription check
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has a subscriber
        if (!$user->subscriber) {
            abort(403, 'No subscription found. Please contact support.');
        }

        // Check if subscription is active
        if (!$user->subscriber->isActive()) {
            if ($user->subscriber->isExpired()) {
                return redirect()->route('subscription.expired');
            }

            if ($user->subscriber->status === 'suspended') {
                abort(403, 'Your subscription has been suspended. Please contact support.');
            }

            if ($user->subscriber->status === 'cancelled') {
                abort(403, 'Your subscription has been cancelled. Please contact support.');
            }
        }

        return $next($request);
    }
}
