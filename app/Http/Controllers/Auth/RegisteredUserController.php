<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'restaurant_address' => 'required|string|max:500',
            'restaurant_phone' => 'required|string|max:50',
            'restaurant_email' => 'required|string|lowercase|email|max:255|unique:restaurants,email',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $restaurant = Restaurant::create([
                'name' => $request->restaurant_name,
                'address' => $request->restaurant_address,
                'phone' => $request->restaurant_phone,
                'email' => $request->restaurant_email,
                'opening_hours' => [],
            ]);

            $branch = Branch::create([
                'restaurant_id' => $restaurant->id,
                'name' => 'Casa matriz',
                'address' => $request->restaurant_address,
                'phone' => $request->restaurant_phone,
                'is_active' => true,
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'restaurant_id' => $restaurant->id,
                'role' => 'owner',
            ]);

            // Los planes los crea el seeder (idempotente); si nunca se corrió, se crean acá.
            if (! SubscriptionPlan::where('slug', 'free')->exists()) {
                app(SubscriptionPlanSeeder::class)->run();
            }
            // Free: sin vencimiento
            $restaurant->assignPlan(SubscriptionPlan::where('slug', 'free')->firstOrFail());

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        session(['current_branch_id' => $user->restaurant->branches()->first()?->id]);

        return redirect(route('dashboard', absolute: false));
    }
}
