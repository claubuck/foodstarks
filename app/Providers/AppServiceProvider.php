<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Detrás de nginx con HTTPS, la petición llega por HTTP; forzar URL base y esquema para que asset() y @vite usen https://
        $appUrl = config('app.url');
        if (config('app.env') === 'production' && $appUrl && str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        // Operador de la plataforma (no confundir con el rol owner/admin de un restaurante)
        Gate::define('superadmin', fn (User $user) => in_array(strtolower($user->email), array_map('strtolower', config('app.superadmins')), true));

        // Quién puede ver el dashboard de Horizon
        Horizon::auth(function ($request) {
            if (app()->environment('local')) {
                return true;
            }

            return $request->user()?->can('superadmin') ?? false;
        });
    }
}
