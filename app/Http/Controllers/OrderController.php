<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Table;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if (! $branchId) {
            return redirect()->route('dashboard');
        }

        $orders = Order::query()
            ->where('branch_id', $branchId)
            ->where('restaurant_id', $user->restaurant_id)
            ->with(['table', 'user', 'orderDetails.product'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))
            ->latest()
            ->paginate(15);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'date']),
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if (! $branchId) {
            return redirect()->route('dashboard');
        }

        $perPage = $request->input('per_page', 6);
        $categoryId = $request->input('categoria');
        $buscar = $request->input('buscar');
        $tableId = $request->input('table_id');
        $orderId = $request->input('order_id');

        $productos = Product::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->when($categoryId, fn ($q, $id) => $q->where('category_id', $id))
            ->when($buscar, fn ($q, $b) => $q->where('name', 'like', '%'.$b.'%'))
            ->paginate($perPage);

        $categorias = ProductCategory::where('restaurant_id', $user->restaurant_id)->get(['id', 'name']);
        $mesas = Table::where('branch_id', $branchId)->orderBy('table_number')->get(['id', 'table_number', 'capacity', 'status']);

        $currentOrder = null;
        if ($orderId) {
            $currentOrder = Order::where('branch_id', $branchId)
                ->where('restaurant_id', $user->restaurant_id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->with(['orderDetails.product'])
                ->find($orderId);
        }

        return Inertia::render('Sales/Create', [
            'productos' => $productos,
            'categorias' => $categorias,
            'mesas' => $mesas,
            'filters' => $request->only(['buscar', 'categoria']),
            'table_id' => $tableId,
            'order_id' => $orderId,
            'current_order' => $currentOrder,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if (! $branchId) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validated();
        $orderId = $request->input('order_id');
        $tableId = $request->input('table_id');

        if ($orderId) {
            $order = Order::where('branch_id', $branchId)
                ->where('restaurant_id', $user->restaurant_id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->find($orderId);

            if ($order) {
                $totalAdded = 0;
                foreach ($validated['items'] as $item) {
                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                    ]);
                    $totalAdded += $item['price'] * $item['quantity'];
                }
                $order->increment('total_amount', $totalAdded);
                $this->broadcastOrderUpdated($branchId);

                return redirect()->route('sales.create', ['table_id' => $order->table_id, 'order_id' => $order->id])
                    ->with('success', 'Ítems agregados al pedido.');
            }
        }

        $totalAmount = 0;
        foreach ($validated['items'] as $item) {
            $totalAmount += $item['price'] * $item['quantity'];
        }

        $order = Order::create([
            'restaurant_id' => $user->restaurant_id,
            'branch_id' => $branchId,
            'user_id' => $user->id,
            'table_id' => $tableId,
            'type' => $validated['type'],
            'payment_method' => $validated['payment_method'],
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'paid' => false,
        ]);

        foreach ($validated['items'] as $item) {
            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
            ]);
        }

        if ($tableId) {
            Table::where('id', $tableId)->where('branch_id', $branchId)->update(['status' => 'occupied']);
        }

        $this->broadcastOrderUpdated($branchId);

        return redirect()->route('orders.index')->with('success', 'Pedido creado correctamente.');
    }

    public function show(Order $order): Response
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId();
        if ($order->restaurant_id !== $user->restaurant_id || $order->branch_id !== $branchId) {
            abort(404);
        }

        $order->load(['table', 'user', 'orderDetails.product']);

        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }

    public function edit(Order $order)
    {
        return redirect()->route('orders.show', $order);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if ($order->restaurant_id !== $user->restaurant_id || $order->branch_id !== $branchId) {
            abort(404);
        }

        $validated = $request->validated();
        $order->update($validated);

        if (isset($validated['status']) && in_array($validated['status'], ['completed', 'cancelled'])) {
            if ($order->table_id) {
                Table::where('id', $order->table_id)->update(['status' => 'available']);
            }
        }

        $this->broadcastOrderUpdated($order->branch_id);

        return redirect()->route('orders.show', $order)->with('success', 'Pedido actualizado.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId();
        if ($order->restaurant_id !== $user->restaurant_id || $order->branch_id !== $branchId) {
            abort(404);
        }

        if ($order->table_id) {
            Table::where('id', $order->table_id)->update(['status' => 'available']);
        }
        $order->delete();

        $this->broadcastOrderUpdated($order->branch_id);

        return redirect()->route('orders.index')->with('success', 'Pedido eliminado.');
    }

    public function cobrar(Order $order): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId();
        if ($order->restaurant_id !== $user->restaurant_id || $order->branch_id !== $branchId) {
            abort(404);
        }

        $order->update(['status' => 'completed', 'paid' => true]);
        if ($order->table_id) {
            Table::where('id', $order->table_id)->where('branch_id', $branchId)->update(['status' => 'available']);
        }

        $this->broadcastOrderUpdated($branchId);

        return redirect()->route('dashboard')->with('success', 'Pedido cobrado.');
    }

    /**
     * Envía el evento OrderUpdated para actualizar el dashboard en tiempo real.
     * Si Reverb no está disponible, se registra el error pero la petición no falla.
     */
    private function broadcastOrderUpdated(string $branchId): void
    {
        try {
            OrderUpdated::dispatch($branchId);
        } catch (BroadcastException $e) {
            Log::warning('Broadcast OrderUpdated falló (Reverb puede no estar disponible)', [
                'branch_id' => $branchId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
