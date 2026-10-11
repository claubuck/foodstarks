<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel del operador de la plataforma (Gate "superadmin", emails en SUPERADMIN_EMAILS):
 * restaurantes con su plan, asignación manual de planes y herramientas de sistema.
 */
class SuperadminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = $request->input('search');

        $restaurants = Restaurant::query()
            ->with(['activeSubscription.plan', 'users' => fn ($q) => $q->where('role', 'owner')->select('id', 'restaurant_id', 'name', 'email')])
            ->withCount(['branches', 'users'])
            ->when($search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Restaurant $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'email' => $r->email,
                'created_at' => $r->created_at,
                'branches_count' => $r->branches_count,
                'users_count' => $r->users_count,
                'owner' => $r->users->first()?->only(['name', 'email']),
                'plan' => $r->activeSubscription?->plan?->only(['id', 'name', 'slug']),
                'period_end' => $r->activeSubscription?->current_period_end,
            ]);

        return Inertia::render('Superadmin/Dashboard', [
            'restaurants' => $restaurants,
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get(['id', 'name', 'slug']),
            'filters' => $request->only('search'),
            'horizon_url' => url(config('horizon.path', 'horizon')),
        ]);
    }

    public function assignPlan(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'days' => 'nullable|integer|min:0|max:3650',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $days = (int) ($validated['days'] ?? 0);
        $restaurant->assignPlan($plan, $days > 0 ? $days : null);

        return back()->with('success', "Plan {$plan->name} asignado a {$restaurant->name}.");
    }
}
