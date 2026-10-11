<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
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

        $reservations = Reservation::where('branch_id', $branchId)
            ->with('table')
            ->when($request->filled('date'), fn ($q) => $q->whereDate('reservation_time', $request->date))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('reservation_time')
            ->paginate(15);

        return Inertia::render('Reservations/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['date', 'status']),
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
        $tables = Table::where('branch_id', $branchId)->orderBy('table_number')->get(['id', 'table_number', 'capacity']);
        return Inertia::render('Reservations/Create', ['tables' => $tables]);
    }

    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if (! $branchId) {
            return redirect()->route('dashboard');
        }

        $table = Table::where('id', $request->table_id)->where('branch_id', $branchId)->firstOrFail();

        Reservation::create([
            'restaurant_id' => $user->restaurant_id,
            'branch_id' => $branchId,
            'table_id' => $request->table_id,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'reservation_time' => $request->reservation_time,
            'party_size' => $request->party_size,
            'status' => 'confirmed',
            'notes' => $request->notes,
        ]);

        $table->syncStatus();

        return redirect()->route('reservations.index')->with('success', 'Reserva creada.');
    }

    public function edit(Reservation $reservation): Response
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId();
        if ($reservation->restaurant_id !== $user->restaurant_id || $reservation->branch_id !== $branchId) {
            abort(404);
        }
        $tables = Table::where('branch_id', $branchId)->orderBy('table_number')->get(['id', 'table_number', 'capacity']);
        $reservation->load('table');
        return Inertia::render('Reservations/Edit', ['reservation' => $reservation, 'tables' => $tables]);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if ($reservation->restaurant_id !== $user->restaurant_id || $reservation->branch_id !== $branchId) {
            abort(404);
        }
        $validated = $request->validated();
        if (isset($validated['table_id'])) {
            Table::where('id', $validated['table_id'])->where('branch_id', $branchId)->firstOrFail();
        }

        $previousTable = $reservation->table;
        $reservation->update($validated);
        $previousTable?->syncStatus();
        $reservation->refresh()->table?->syncStatus();

        return redirect()->route('reservations.index')->with('success', 'Reserva actualizada.');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId();
        if ($reservation->restaurant_id !== $user->restaurant_id || $reservation->branch_id !== $branchId) {
            abort(404);
        }
        $table = $reservation->table;
        $reservation->delete();
        $table?->syncStatus();
        return redirect()->route('reservations.index')->with('success', 'Reserva eliminada.');
    }
}
