<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SuperadminDashboardController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/subscription/required', [SubscriptionController::class, 'required'])
        ->name('subscription.required');
    Route::get('/subscription', [SubscriptionController::class, 'index'])
        ->name('subscription.index');

    // Operador de la plataforma (SUPERADMIN_EMAILS): restaurantes/planes y diagnóstico
    Route::middleware(['can:superadmin', 'branch.set'])->prefix('superadmin')->group(function () {
        Route::get('/', SuperadminDashboardController::class)->name('superadmin.dashboard');
        Route::post('/restaurants/{restaurant}/plan', [SuperadminDashboardController::class, 'assignPlan'])
            ->name('superadmin.restaurants.plan');
        Route::get('/restaurants/{restaurant}', [SuperadminDashboardController::class, 'show'])
            ->name('superadmin.restaurants.show');
        Route::get('/users', [SuperadminDashboardController::class, 'users'])->name('superadmin.users');
        Route::post('/subscriptions/{subscription}/payment', [SuperadminDashboardController::class, 'payment'])
            ->name('superadmin.subscriptions.payment');

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

        // Prueba de cola (despacha un job; ver en Horizon)
        Route::get('/queue-test', function () {
            \App\Jobs\TestQueueJob::dispatch('¡Hola desde la cola!');

            return response()->json(['message' => 'Job despachado. Revisá Horizon.']);
        })->name('queue.test');

        // Diagnóstico: conexión de colas y Redis
        Route::get('/queue-diagnostic', function () {
            $redisOk = false;
            $redisError = null;
            try {
                \Illuminate\Support\Facades\Redis::connection()->ping();
                $redisOk = true;
            } catch (Throwable $e) {
                $redisError = $e->getMessage();
            }

            return response()->json([
                'queue_connection' => config('queue.default'),
                'redis_host' => config('database.redis.default.host'),
                'redis_port' => config('database.redis.default.port'),
                'redis_reachable' => $redisOk,
                'redis_error' => $redisError,
            ]);
        })->name('queue.diagnostic');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['subscription.active', 'branch.set'])->group(function () {
        Route::post('/branch/switch', [BranchController::class, 'switch'])
            ->name('branch.switch');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/sales/create', [OrderController::class, 'create'])->name('sales.create');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
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
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

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
