<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->restaurant_id) {
            return $next($request);
        }

        $subscription = $user->restaurant->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->where('current_period_end', '>=', now())
            ->latest()
            ->first();

        if (! $subscription) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu suscripción no está activa.'], 403);
            }
            return redirect()->route('subscription.required');
        }

        return $next($request);
    }
}
