<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTableRequest;
use App\Http\Requests\UpdateTableRequest;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TableController extends Controller
{
    /**
     * @return Response|RedirectResponse
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if (! $branchId) {
            return redirect()->route('dashboard');
        }

        $tables = Table::where('branch_id', $branchId)->orderBy('table_number')->get();

        return Inertia::render('Tables/Index', [
            'tables' => $tables,
        ]);
    }

    /**
     * @return Response|RedirectResponse
     */
    public function create()
    {
        $branchId = $this->currentBranchId();
        if (! $branchId) {
            return redirect()->route('dashboard');
        }
        return Inertia::render('Tables/Create');
    }

    public function store(StoreTableRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if (! $branchId) {
            return redirect()->route('dashboard');
        }

        Table::create([
            'restaurant_id' => $user->restaurant_id,
            'branch_id' => $branchId,
            'table_number' => $request->table_number,
            'capacity' => $request->capacity,
            'status' => 'available',
        ]);

        return redirect()->route('tables.index')->with('success', 'Mesa creada correctamente.');
    }

    public function edit(Table $table): Response
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId();
        if ($table->restaurant_id !== $user->restaurant_id || $table->branch_id !== $branchId) {
            abort(404);
        }
        return Inertia::render('Tables/Edit', ['table' => $table]);
    }

    public function update(UpdateTableRequest $request, Table $table): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if ($table->restaurant_id !== $user->restaurant_id || $table->branch_id !== $branchId) {
            abort(404);
        }

        $table->update($request->validated());
        return redirect()->route('tables.index')->with('success', 'Mesa actualizada.');
    }

    public function destroy(Table $table): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId();
        if ($table->restaurant_id !== $user->restaurant_id || $table->branch_id !== $branchId) {
            abort(404);
        }
        if ($table->orders()->active()->exists()) {
            return redirect()->route('tables.index')->with('error', 'La mesa tiene un pedido abierto: cobralo o cancelalo antes de eliminarla.');
        }
        if ($table->reservations()->whereIn('status', ['pending', 'confirmed'])->where('reservation_time', '>=', now())->exists()) {
            return redirect()->route('tables.index')->with('error', 'La mesa tiene reservas próximas: reasignalas o cancelalas antes de eliminarla.');
        }

        $table->delete();
        return redirect()->route('tables.index')->with('success', 'Mesa eliminada.');
    }
}
