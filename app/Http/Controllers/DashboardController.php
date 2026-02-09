<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
                ->first();

            return [
                'id' => $table->id,
                'table_number' => $table->table_number,
                'capacity' => $table->capacity,
                'status' => $table->status,
                'current_order' => $currentOrder ? [
                    'id' => $currentOrder->id,
                    'status' => $currentOrder->status,
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

        $stats = [
            'sales_today' => round($salesToday, 2),
            'tables_occupied' => $tablesOccupied,
            'tables_total' => $tablesTotal,
            'orders_active' => $ordersActive,
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'mesas' => $mesas,
        ]);
    }
}
