<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! in_array($request->user()?->role, ['owner', 'manager'])) {
                abort(403, 'No autorizado.');
            }
            return $next($request);
        });
    }

    public function edit(Request $request): Response
    {
        $restaurant = $request->user()->restaurant;
        $restaurant->loadMissing('branches');

        return Inertia::render('Settings/Restaurant', [
            'restaurant' => $restaurant->only(['id', 'name', 'address', 'phone', 'email', 'logo_url', 'opening_hours']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:50',
            'email' => 'required|string|lowercase|email|max:255|unique:restaurants,email,' . $restaurant->id,
            'logo_url' => 'nullable|image|max:2048',
            'opening_hours' => 'nullable|array',
        ]);

        if ($request->hasFile('logo_url')) {
            if ($restaurant->logo_url) {
                $oldPath = str_replace(asset('storage/'), '', $restaurant->logo_url);
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $path = $request->file('logo_url')->store('restaurants/' . $restaurant->id, 'public');
            $validated['logo_url'] = asset('storage/' . $path);
        } else {
            unset($validated['logo_url']);
        }

        $restaurant->update($validated);

        return redirect()->route('restaurant.edit')->with('success', 'Restaurante actualizado.');
    }
}
