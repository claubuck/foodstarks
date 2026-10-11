<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $restaurant = $user?->restaurant;
        $branches = $restaurant
            ? $restaurant->branches()->where('is_active', true)->get(['id', 'name'])
            : collect();
        $currentBranchId = $request->attributes->get('current_branch_id') ?? session('current_branch_id');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'restaurant' => $restaurant?->only(['id', 'name']),
                'branches' => $branches,
                'current_branch_id' => $currentBranchId,
                'is_superadmin' => (bool) $user?->can('superadmin'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'session' => [
                'flash' => [
                    'success' => fn () => $request->session()->get('success'),
                    'error' => fn () => $request->session()->get('error'),
                    'info' => fn () => $request->session()->get('info'),
                ],
            ],
        ];
    }
}
