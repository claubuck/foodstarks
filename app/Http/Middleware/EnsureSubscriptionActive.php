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

        if (! $user->restaurant->activeSubscription()->exists()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu suscripción no está activa.'], 403);
            }

            return redirect()->route('subscription.required');
        }

        return $next($request);
    }
}
