<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mesas: pasan a "reserved" en la ventana de cada reserva y vuelven a "available" al vencer
Artisan::command('tables:sync-status', function () {
    $count = 0;
    \App\Models\Table::query()->each(function (\App\Models\Table $table) use (&$count) {
        $before = $table->status;
        $table->syncStatus();
        $count += (int) ($before !== $table->status);
    });
    $this->info("Mesas actualizadas: {$count}");
})->purpose('Recalcula el estado de las mesas según pedidos activos y reservas');

Schedule::command('tables:sync-status')->everyFiveMinutes()->withoutOverlapping();
