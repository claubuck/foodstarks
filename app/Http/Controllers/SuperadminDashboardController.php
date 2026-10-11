<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuperadminDashboardController extends Controller
{
    /**
     * Panel de superadmin: solo owner y admin.
     * Muestra enlaces a Prueba tiempo real y Horizon.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        if (! in_array($user->role, ['owner', 'admin'])) {
            abort(403, 'No tenés permiso para acceder al panel de administración.');
        }

        $horizonPath = config('horizon.path', 'horizon');
        $horizonUrl = url($horizonPath);

        return Inertia::render('Superadmin/Dashboard', [
            'horizon_url' => $horizonUrl,
        ]);
    }
}
