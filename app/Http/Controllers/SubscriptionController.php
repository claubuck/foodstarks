<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function required(): Response
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get();

        return Inertia::render('Subscription/Required', [
            'plans' => $plans,
            'support_email' => config('app.support_email'),
        ]);
    }

    public function index(Request $request): Response
    {
        $subscription = $request->user()->restaurant->activeSubscription()->with('plan')->first();

        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get();

        return Inertia::render('Subscription/Index', [
            'subscription' => $subscription,
            'plans' => $plans,
            'support_email' => config('app.support_email'),
        ]);
    }
}
