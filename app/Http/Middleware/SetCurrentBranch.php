<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->restaurant_id) {
            return $next($request);
        }

        $branchId = session('current_branch_id');
        $branches = $user->restaurant->branches()->where('is_active', true)->pluck('id');

        if (! $branchId || ! $branches->contains($branchId)) {
            $first = $user->restaurant->branches()->where('is_active', true)->first();
            if ($first) {
                $branchId = $first->id;
                session(['current_branch_id' => $branchId]);
            }
        }

        if ($branchId) {
            $request->attributes->set('current_branch_id', $branchId);
        }

        return $next($request);
    }
}
