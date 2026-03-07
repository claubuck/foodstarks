<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
    }
}
