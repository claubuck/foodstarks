<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TableController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Prueba de cola (despacha un job; ver en Horizon o en la tabla jobs)
Route::get('/queue-test', function () {
    \App\Jobs\TestQueueJob::dispatch('¡Hola desde la cola!');
    return response()->json([
        'message' => 'Job despachado. Revisá Horizon en /horizon o la tabla jobs.',
    ]);
})->name('queue.test');

// Diagnóstico: conexión de colas y Redis (para ver si Horizon puede procesar)
Route::get('/queue-diagnostic', function () {
    $connection = config('queue.default');
    $redisHost = config('database.redis.default.host');
    $redisPort = config('database.redis.default.port');
    $redisOk = false;
    $redisError = null;
    try {
        \Illuminate\Support\Facades\Redis::connection()->ping();
        $redisOk = true;
    } catch (Throwable $e) {
        $redisError = $e->getMessage();
    }
    return response()->json([
        'queue_connection' => $connection,
        'redis_host' => $redisHost,
        'redis_port' => $redisPort,
        'redis_reachable' => $redisOk,
        'redis_error' => $redisError,
        'hint' => $connection === 'redis' && $redisOk
            ? 'Todo OK: los jobs van a Redis y Horizon puede procesarlos.'
            : ($connection !== 'redis'
                ? 'Cambiá QUEUE_CONNECTION a redis y reiniciá app + horizon.'
                : 'Redis no responde; revisá que el contenedor redis esté arriba.'),
    ]);
})->name('queue.diagnostic');

// Prueba de conexión a Laravel Reverb (WebSockets)
Route::get('/reverb-test', function () {
    return Inertia::render('ReverbTest', [
        'reverbHost' => config('broadcasting.connections.reverb.options.host'),
        'reverbPort' => config('broadcasting.connections.reverb.options.port'),
        'reverbScheme' => config('broadcasting.connections.reverb.options.scheme'),
    ]);
})->name('reverb.test');

// Diagnóstico: qué usa la app para enviar a Reverb (app_id, host, etc.)
Route::get('/reverb-diagnostic', function () {
    $conn = config('broadcasting.connections.reverb');
    return response()->json([
        'app_id' => $conn['app_id'] ?? null,
        'key' => $conn['key'] ?? null,
        'host' => $conn['options']['host'] ?? null,
        'port' => $conn['options']['port'] ?? null,
        'hint' => 'El app_id debe ser exactamente "foodstarks" y coincidir con config/reverb.php en el contenedor Reverb.',
    ]);
})->name('reverb.diagnostic');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/subscription/required', [SubscriptionController::class, 'required'])
        ->name('subscription.required');
        Route::get('/subscription', [SubscriptionController::class, 'index'])
            ->name('subscription.index');

        Route::middleware(['subscription.active', 'branch.set'])->group(function () {
            Route::post('/branch/switch', [BranchController::class, 'switch'])
                ->name('branch.switch');
            Route::get('/dashboard', \App\Http\Controllers\DashboardController::class)->name('dashboard');

            // Panel superadmin (solo owner/admin): Prueba tiempo real + Horizon
            Route::get('/superadmin', \App\Http\Controllers\SuperadminDashboardController::class)->name('superadmin.dashboard');

            // Prueba de tiempo real: ver estado Echo/Reverb y enviar evento order.updated
            Route::get('/realtime-test', function () {
                $branchId = request()->attributes->get('current_branch_id') ?? session('current_branch_id');
                $reverb = config('broadcasting.connections.reverb');
                return Inertia::render('RealtimeTest', [
                    'branch_id' => $branchId,
                    'reverbHost' => $reverb['public_host'] ?? $reverb['options']['host'] ?? 'localhost',
                    'reverbPort' => $reverb['public_port'] ?? $reverb['options']['port'] ?? 8080,
                    'reverbScheme' => $reverb['public_scheme'] ?? $reverb['options']['scheme'] ?? 'http',
                ]);
            })->name('realtime.test');
            Route::post('/realtime-test/send', function () {
                $branchId = request()->attributes->get('current_branch_id') ?? session('current_branch_id');
                if (! $branchId) {
                    return response()->json(['ok' => false, 'error' => 'No hay sucursal seleccionada.'], 400);
                }
                try {
                    \App\Events\OrderUpdated::dispatch($branchId);
                    return response()->json(['ok' => true]);
                } catch (\Illuminate\Broadcasting\BroadcastException $e) {
                    return response()->json(['ok' => false, 'error' => $e->getMessage()], 200);
                }
            })->name('realtime.send');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/sales/create', [OrderController::class, 'create'])->name('sales.create');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/edit/{product}', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/update/{product}', [ProductController::class, 'update'])->name('products.update');

        Route::get('/orders/index', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/edit/{order}', [OrderController::class, 'edit'])->name('orders.edit');
        Route::put('/orders/update/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/destroy/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::get('/orders/show/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/cobrar', [OrderController::class, 'cobrar'])->name('orders.cobrar');

        Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
        Route::get('/tables/create', [TableController::class, 'create'])->name('tables.create');
        Route::post('/tables', [TableController::class, 'store'])->name('tables.store');
        Route::get('/tables/{table}/edit', [TableController::class, 'edit'])->name('tables.edit');
        Route::put('/tables/{table}', [TableController::class, 'update'])->name('tables.update');
        Route::delete('/tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');

        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');

        Route::get('/settings/restaurant', [RestaurantController::class, 'edit'])->name('restaurant.edit');
        Route::put('/settings/restaurant', [RestaurantController::class, 'update'])->name('restaurant.update');

        Route::get('/product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.index');
        Route::post('/product-categories', [ProductCategoryController::class, 'store'])->name('product-categories.store');
        Route::put('/product-categories/{product_category}', [ProductCategoryController::class, 'update'])->name('product-categories.update');
        Route::delete('/product-categories/{product_category}', [ProductCategoryController::class, 'destroy'])->name('product-categories.destroy');

        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::get('/reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
    });
});

require __DIR__.'/auth.php';
