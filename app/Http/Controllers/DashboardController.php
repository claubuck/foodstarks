<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if (! $branchId) {
            return Inertia::render('Dashboard', [
                'stats' => ['sales_today' => 0, 'tables_occupied' => 0, 'tables_total' => 0, 'orders_active' => 0],
                'mesas' => [],
                'pedidos_sin_mesa' => [],
                'branch_id' => null,
            ]);
        }

        $tables = Table::where('branch_id', $branchId)->orderBy('table_number')->get();
        $tablesTotal = $tables->count();
        $ordersActive = Order::where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();
        $salesToday = Order::where('branch_id', $branchId)
            ->whereIn('status', ['completed'])
            ->where('paid', true)
            ->whereDate('created_at', today())
            ->sum('total_amount');

        $mesas = $tables->map(function ($table) {
            $currentOrder = Order::where('branch_id', $table->branch_id)
                ->where('table_id', $table->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->with(['orderDetails.product'])
                ->latest('id')
                ->first();

            return [
                'id' => $table->id,
                'table_number' => $table->table_number,
                'capacity' => $table->capacity,
                'status' => $table->status,
                'current_order' => $currentOrder ? [
                    'id' => $currentOrder->id,
                    'status' => $currentOrder->status,
                    'payment_method' => $currentOrder->payment_method,
                    'total_amount' => $currentOrder->total_amount,
                    'items' => $currentOrder->orderDetails->map(fn ($d) => [
                        'product_name' => $d->product?->name,
                        'quantity' => $d->quantity,
                        'unit_price' => $d->unit_price,
                    ]),
                ] : null,
            ];
        });

        $tablesOccupied = $mesas->where('status', 'occupied')->count();

        // Pedidos activos sin mesa (para llevar, delivery, etc.)
        $pedidosSinMesa = Order::where('branch_id', $branchId)
            ->whereNull('table_id')
            ->whereIn('status', ['pending', 'in_progress'])
            ->with(['orderDetails.product'])
            ->latest('id')
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'status' => $order->status,
                'type' => $order->type ?? 'takeaway',
                'payment_method' => $order->payment_method,
                'total_amount' => $order->total_amount,
                'items' => $order->orderDetails->map(fn ($d) => [
                    'product_name' => $d->product?->name,
                    'quantity' => $d->quantity,
                    'unit_price' => $d->unit_price,
                ]),
            ])
            ->values()
            ->all();

        $stats = [
            'sales_today' => round($salesToday, 2),
            'tables_occupied' => $tablesOccupied,
            'tables_total' => $tablesTotal,
            'orders_active' => $ordersActive,
        ];

        // Checklist de primeros pasos (se oculta cuando todo está cargado)
        $setup = [
            'categories' => ProductCategory::where('restaurant_id', $user->restaurant_id)->exists(),
            'products' => Product::where('branch_id', $branchId)->exists(),
            'tables' => $tablesTotal > 0,
        ];

        return Inertia::render('Dashboard', [
            'setup' => $setup,
            'stats' => $stats,
            'mesas' => $mesas,
            'pedidos_sin_mesa' => $pedidosSinMesa,
            'branch_id' => $branchId,
        ]);
    }
}
