<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel del operador de la plataforma (Gate "superadmin", emails en SUPERADMIN_EMAILS):
 * métricas del negocio, restaurantes con su plan y estado de pago, usuarios,
 * asignación manual de planes, registro de cobros y herramientas de sistema.
 */
class SuperadminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $restaurants = Restaurant::query()
            ->with(['activeSubscription.plan', 'users' => fn ($q) => $q->where('role', 'owner')->select('id', 'restaurant_id', 'name', 'email')])
            ->withCount(['branches', 'users', 'orders as orders_30d_count' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))])
            ->withMax('users', 'last_login')
            ->withMax('orders', 'created_at')
            ->when($search, fn ($q, $s) => $this->search($q, ['name', 'email'], $s))
            ->when($status === 'pending', fn ($q) => $q->whereHas('subscriptions', fn ($s) => $s->current()->unpaid()))
            ->when($status === 'paid', fn ($q) => $q->whereHas('subscriptions', fn ($s) => $s->current()->whereNotNull('paid_at')))
            ->when($status === 'free', fn ($q) => $q->whereHas('subscriptions', fn ($s) => $s->current()->whereHas('plan', fn ($p) => $p->where('price_monthly', '<=', 0))))
            ->when($status === 'expiring', fn ($q) => $q->whereHas('subscriptions', fn ($s) => $s->current()->where('current_period_end', '<=', now()->addDays(7))))
            ->when($status === 'no_plan', fn ($q) => $q->whereDoesntHave('subscriptions', fn ($s) => $s->current()))
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
                'orders_30d_count' => $r->orders_30d_count,
                'last_login' => $r->users_max_last_login,
                'last_order_at' => $r->orders_max_created_at,
                'owner' => $r->users->first()?->only(['name', 'email']),
                'subscription' => $this->subscriptionData($r->activeSubscription),
            ]);

        return Inertia::render('Superadmin/Dashboard', [
            'stats' => $this->stats(),
            'restaurants' => $restaurants,
            'plans' => $this->plans(),
            'filters' => $request->only('search', 'status'),
            'horizon_url' => url(config('horizon.path', 'horizon')),
        ]);
    }

    public function show(Restaurant $restaurant): Response
    {
        $restaurant->load(['branches:id,restaurant_id,name,is_active', 'users' => fn ($q) => $q->orderBy('created_at')]);
        $since = now()->subDays(30);

        return Inertia::render('Superadmin/Restaurant', [
            'restaurant' => $restaurant->only(['id', 'name', 'email', 'phone', 'address', 'logo_url', 'created_at']),
            'branches' => $restaurant->branches,
            'users' => $restaurant->users->map(fn (User $u) => $u->only(['id', 'name', 'email', 'role', 'last_login', 'email_verified_at', 'created_at'])),
            'subscriptions' => $restaurant->subscriptions()->with('plan')->latest('id')->get()
                ->map(fn (Subscription $s) => $this->subscriptionData($s)),
            'current_subscription_id' => $restaurant->activeSubscription?->id,
            'usage' => [
                'orders_total' => $restaurant->orders()->count(),
                'orders_30d' => $restaurant->orders()->where('created_at', '>=', $since)->count(),
                'sales_30d' => (float) $restaurant->orders()->where('status', 'completed')->where('created_at', '>=', $since)->sum('total_amount'),
                'last_order_at' => $restaurant->orders()->max('created_at'),
                'products' => $restaurant->products()->count(),
                'tables' => $restaurant->tables()->count(),
            ],
            'plans' => $this->plans(),
        ]);
    }

    public function users(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $users = User::query()
            ->with('restaurant:id,name', 'restaurant.activeSubscription.plan')
            ->when($search, fn ($q, $s) => $this->search($q, ['name', 'email'], $s))
            ->when($role, fn ($q, $r) => $q->where('role', $r))
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn (User $u) => [
                ...$u->only(['id', 'name', 'email', 'role', 'last_login', 'email_verified_at', 'created_at']),
                'restaurant' => $u->restaurant?->only(['id', 'name']),
                'subscription' => $this->subscriptionData($u->restaurant?->activeSubscription),
            ]);

        return Inertia::render('Superadmin/Users', [
            'users' => $users,
            'filters' => $request->only('search', 'role'),
            'stats' => [
                'total' => User::count(),
                'active_7d' => User::where('last_login', '>=', now()->subDays(7))->count(),
                'new_30d' => User::where('created_at', '>=', now()->subDays(30))->count(),
            ],
        ]);
    }

    public function assignPlan(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'days' => 'nullable|integer|min:0|max:3650',
            'paid' => 'boolean',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $days = (int) ($validated['days'] ?? 0);
        $subscription = $restaurant->assignPlan($plan, $days > 0 ? $days : null);

        if (! empty($validated['paid']) && ! $plan->isFree()) {
            $subscription->update([
                'paid_at' => now(),
                'amount_paid' => $validated['amount_paid'] ?? $plan->price_monthly,
                'payment_reference' => $validated['payment_reference'] ?? null,
            ]);
        }

        return back()->with('success', "Plan {$plan->name} asignado a {$restaurant->name}.");
    }

    /** Marca (o desmarca) como pagado el periodo de una suscripción. */
    public function payment(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'paid' => 'required|boolean',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        if ($validated['paid']) {
            $subscription->update([
                'paid_at' => now(),
                'amount_paid' => $validated['amount_paid'] ?? $subscription->plan->price_monthly,
                'payment_reference' => $validated['payment_reference'] ?? null,
            ]);

            return back()->with('success', 'Pago registrado.');
        }

        $subscription->update(['paid_at' => null, 'amount_paid' => null, 'payment_reference' => null]);

        return back()->with('success', 'Pago anulado.');
    }

    /** Métricas del negocio para las tarjetas del panel. */
    private function stats(): array
    {
        $current = Subscription::current()->with('plan:id,name,slug,price_monthly')->get(['id', 'restaurant_id', 'plan_id', 'current_period_end', 'paid_at']);
        // Una vigente por restaurante (la más nueva), igual que activeSubscription()
        $current = $current->sortByDesc('id')->unique('restaurant_id');
        $paidPlans = $current->filter(fn ($s) => (float) $s->plan?->price_monthly > 0);

        return [
            'restaurants' => Restaurant::count(),
            'restaurants_new_30d' => Restaurant::where('created_at', '>=', now()->subDays(30))->count(),
            'users' => User::count(),
            'users_active_7d' => User::where('last_login', '>=', now()->subDays(7))->count(),
            'paying' => $paidPlans->whereNotNull('paid_at')->count(),
            'pending_payment' => $paidPlans->whereNull('paid_at')->count(),
            'mrr' => (float) $paidPlans->sum(fn ($s) => $s->plan->price_monthly),
            'collected_month' => (float) Subscription::where('paid_at', '>=', now()->startOfMonth())->sum('amount_paid'),
            'expiring_7d' => $current->filter(fn ($s) => $s->current_period_end && $s->current_period_end->lte(now()->addDays(7)))->count(),
            'no_plan' => Restaurant::whereDoesntHave('subscriptions', fn ($s) => $s->current())->count(),
            'orders_today' => Order::where('created_at', '>=', now()->startOfDay())->count(),
            'by_plan' => $current->groupBy(fn ($s) => $s->plan?->name ?? '—')->map->count(),
        ];
    }

    private function plans()
    {
        return SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get(['id', 'name', 'slug', 'price_monthly']);
    }

    private function subscriptionData(?Subscription $s): ?array
    {
        if (! $s) {
            return null;
        }

        return [
            'id' => $s->id,
            'status' => $s->status,
            'plan' => $s->plan?->only(['id', 'name', 'slug', 'price_monthly']),
            'period_start' => $s->current_period_start,
            'period_end' => $s->current_period_end,
            'payment_status' => $s->paymentStatus(),
            'paid_at' => $s->paid_at,
            'amount_paid' => $s->amount_paid,
            'payment_reference' => $s->payment_reference,
        ];
    }

    /** Búsqueda sin distinguir mayúsculas (Postgres en prod, SQLite en tests). */
    private function search(Builder $query, array $columns, string $term): Builder
    {
        $term = '%'.mb_strtolower($term).'%';

        return $query->where(function ($q) use ($columns, $term) {
            foreach ($columns as $column) {
                $q->orWhereRaw("lower({$column}) like ?", [$term]);
            }
        });
    }
}
