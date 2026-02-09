<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (in_array($request->route()?->getName(), ['branch.switch'])) {
                return $next($request);
            }
            if (! in_array($request->user()?->role, ['owner', 'manager'])) {
                abort(403, 'No autorizado.');
            }
            return $next($request);
        });
    }

    public function switch(Request $request): RedirectResponse
    {
        $request->validate(['branch_id' => 'required|string|uuid']);

        $user = $request->user();
        $branch = $user->restaurant?->branches()->where('id', $request->branch_id)->where('is_active', true)->first();

        if ($branch) {
            session(['current_branch_id' => $branch->id]);
        }

        return redirect()->back();
    }

    public function index(Request $request): Response
    {
        $branches = $request->user()->restaurant->branches()->orderBy('name')->get();

        return Inertia::render('Settings/Branches', [
            'branches' => $branches,
        ]);
    }

    /**
     * @return Response|RedirectResponse
     */
    public function create(Request $request)
    {
        $restaurant = $request->user()->restaurant;
        $subscription = $restaurant->subscriptions()->whereIn('status', ['trial', 'active'])->with('plan')->latest()->first();
        $maxBranches = $subscription?->plan?->max_branches ?? 1;
        $currentCount = $restaurant->branches()->count();

        if ($currentCount >= $maxBranches) {
            return redirect()->route('branches.index')->with('error', "Has alcanzado el límite de {$maxBranches} sucursales de tu plan.");
        }

        return Inertia::render('Settings/BranchForm', [
            'branch' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;
        $subscription = $restaurant->subscriptions()->whereIn('status', ['trial', 'active'])->with('plan')->latest()->first();
        $maxBranches = $subscription?->plan?->max_branches ?? 1;
        if ($restaurant->branches()->count() >= $maxBranches) {
            return redirect()->route('branches.index')->with('error', "Límite de {$maxBranches} sucursales alcanzado.");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'opening_hours' => 'nullable|array',
        ]);

        Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'opening_hours' => $validated['opening_hours'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('branches.index')->with('success', 'Sucursal creada.');
    }

    public function edit(Request $request, Branch $branch): Response
    {
        $user = $request->user();
        if ($branch->restaurant_id !== $user->restaurant_id) {
            abort(404);
        }
        return Inertia::render('Settings/BranchForm', [
            'branch' => $branch->only(['id', 'name', 'address', 'phone', 'opening_hours', 'is_active']),
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $user = $request->user();
        if ($branch->restaurant_id !== $user->restaurant_id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'opening_hours' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Sucursal actualizada.');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        $user = $request->user();
        if ($branch->restaurant_id !== $user->restaurant_id) {
            abort(404);
        }
        if ($user->restaurant->branches()->count() <= 1) {
            return redirect()->route('branches.index')->with('error', 'No puedes eliminar la única sucursal.');
        }
        if (session('current_branch_id') === $branch->id) {
            $other = $user->restaurant->branches()->where('id', '!=', $branch->id)->first();
            if ($other) {
                session(['current_branch_id' => $other->id]);
            }
        }
        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'Sucursal eliminada.');
    }
}
