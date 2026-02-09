<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! in_array($user->role, ['owner', 'manager'])) {
                abort(403, 'No autorizado.');
            }
            return $next($request);
        });
    }

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $employees = User::where('restaurant_id', $user->restaurant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'created_at']);

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $restaurant = $user->restaurant;

        $subscription = $restaurant->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->where('current_period_end', '>=', now())
            ->with('plan')
            ->latest()
            ->first();

        $maxUsers = $subscription?->plan?->max_users ?? 5;
        $currentCount = User::where('restaurant_id', $restaurant->id)->count();
        if ($currentCount >= $maxUsers) {
            return redirect()->route('employees.index')
                ->with('error', "Has alcanzado el límite de {$maxUsers} usuarios de tu plan.");
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:waiter,chef,manager',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'restaurant_id' => $restaurant->id,
            'role' => $request->role,
        ]);

        return redirect()->route('employees.index')->with('success', 'Empleado agregado correctamente.');
    }
}
