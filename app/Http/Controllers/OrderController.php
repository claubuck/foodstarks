<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->paginate(15)
            ->withQueryString();

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

        $perPage = min((int) $request->input('per_page', 12), 48);
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
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $categorias = ProductCategory::where('restaurant_id', $user->restaurant_id)->orderBy('name')->get(['id', 'name']);
        $mesas = Table::where('branch_id', $branchId)->orderBy('table_number')->get(['id', 'table_number', 'capacity', 'status']);

        // Si llega una mesa con pedido abierto, se trabaja sobre ese pedido
        $currentOrder = Order::where('branch_id', $branchId)
            ->where('restaurant_id', $user->restaurant_id)
            ->active()
            ->with(['orderDetails.product'])
            ->when(
                $orderId,
                fn ($q) => $q->whereKey($orderId),
                fn ($q) => $q->whereNotNull('table_id')->where('table_id', $tableId ?: 0)
            )
            ->latest('id')
            ->first();

        return Inertia::render('Sales/Create', [
            'productos' => $productos,
            'categorias' => $categorias,
            'mesas' => $mesas,
            'filters' => $request->only(['buscar', 'categoria']),
            'table_id' => $currentOrder?->table_id ?? $tableId,
            'order_id' => $currentOrder?->id,
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
        $tableId = $validated['table_id'] ?? null;

        $order = DB::transaction(function () use ($validated, $user, $branchId, $tableId) {
            // Pedido destino: el indicado, o el abierto de la mesa, o uno nuevo
            $order = null;
            if (! empty($validated['order_id'])) {
                $order = Order::where('branch_id', $branchId)->active()->lockForUpdate()->find($validated['order_id']);
            } elseif ($tableId) {
                $order = Order::where('branch_id', $branchId)->where('table_id', $tableId)->active()->lockForUpdate()->latest('id')->first();
            }

            if (! $order) {
                $order = Order::create([
                    'restaurant_id' => $user->restaurant_id,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                    'table_id' => $tableId,
                    'type' => $tableId ? 'dine_in' : $validated['type'],
                    'payment_method' => $validated['payment_method'],
                    'total_amount' => 0,
                    'status' => 'pending',
                    'paid' => false,
                ]);
            }

            // Precios siempre desde la base
            $prices = Product::whereIn('id', collect($validated['items'])->pluck('product_id'))->pluck('price', 'id');
            foreach ($validated['items'] as $item) {
                $order->orderDetails()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $prices[$item['product_id']],
                    'notes' => $item['notes'] ?? null,
                ]);
            }
            $order->recalculateTotal();

            return $order;
        });

        $order->table?->syncStatus();
        $this->broadcastOrderUpdated($branchId);

        $message = $order->wasRecentlyCreated ? 'Pedido creado correctamente.' : 'Ítems agregados al pedido.';

        return redirect()->route('dashboard')->with('success', $message);
    }

    public function show(Order $order): Response
    {
        $this->ensureInBranch($order);

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
        $this->ensureInBranch($order);

        $order->update($request->validated());
        $order->table?->syncStatus();

        $this->broadcastOrderUpdated($order->branch_id);

        return redirect()->route('orders.show', $order)->with('success', 'Pedido actualizado.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->ensureInBranch($order);

        $table = $order->table;
        DB::transaction(function () use ($order) {
            // order_details no tiene cascade en la FK
            $order->orderDetails()->delete();
            $order->delete();
        });
        $table?->syncStatus();

        $this->broadcastOrderUpdated($order->branch_id);

        return redirect()->route('orders.index')->with('success', 'Pedido eliminado.');
    }

    public function cobrar(Request $request, Order $order): RedirectResponse
    {
        $this->ensureInBranch($order);

        $validated = $request->validate([
            'payment_method' => 'nullable|in:cash,card,transfer',
        ]);

        $order->update([
            'status' => 'completed',
            'paid' => true,
            'payment_method' => $validated['payment_method'] ?? $order->payment_method,
        ]);
        $order->table?->syncStatus();

        $this->broadcastOrderUpdated($order->branch_id);

        return redirect()->route('dashboard')->with('success', 'Pedido cobrado.');
    }

    private function ensureInBranch(Order $order): void
    {
        if ($order->restaurant_id !== auth()->user()->restaurant_id || $order->branch_id !== $this->currentBranchId()) {
            abort(404);
        }
    }

    /**
     * Envía el evento OrderUpdated para actualizar el dashboard en tiempo real.
     * Si Reverb no está disponible, se registra el error pero la petición no falla.
     */
    private function broadcastOrderUpdated(string $branchId): void
    {
        try {
            OrderUpdated::dispatch($branchId);
        } catch (\Throwable $e) {
            // Reverb caído o Redis (cola) inaccesible: el pedido ya se guardó, no devolver 500.
            Log::warning('Broadcast OrderUpdated falló (Reverb o Redis no disponibles)', [
                'branch_id' => $branchId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
