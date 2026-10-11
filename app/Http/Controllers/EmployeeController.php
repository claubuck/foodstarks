<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            'max_users' => $user->restaurant->currentPlan()?->max_users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $restaurant = auth()->user()->restaurant;

        $maxUsers = $restaurant->currentPlan()?->max_users ?? 5;
        if ($restaurant->users()->count() >= $maxUsers) {
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

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->ensureManageable($employee);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => 'required|in:waiter,chef,manager',
        ]);

        $employee->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);
        if (! empty($validated['password'])) {
            $employee->password = Hash::make($validated['password']);
        }
        $employee->save();

        return redirect()->route('employees.index')->with('success', 'Empleado actualizado.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        $this->ensureManageable($employee);

        // Sus pedidos quedan (orders.user_id hace cascade): se reasignan al usuario que da de baja
        $employee->getConnection()->table('orders')
            ->where('user_id', $employee->id)
            ->update(['user_id' => auth()->id()]);
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Empleado eliminado.');
    }

    /** Solo empleados del mismo restaurante; el dueño y el propio usuario no se gestionan desde acá. */
    private function ensureManageable(User $employee): void
    {
        $user = auth()->user();
        if ($employee->restaurant_id !== $user->restaurant_id) {
            abort(404);
        }
        if ($employee->role === 'owner' || $employee->id === $user->id) {
            abort(403, 'Este usuario se gestiona desde su perfil.');
        }
    }
}
