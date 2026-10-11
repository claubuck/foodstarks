<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    /** @use HasFactory<\Database\Factories\TableFactory> */
    use HasFactory;

    protected $fillable = [
        'table_number',
        'capacity',
        'status',
        'qr_code_url',
        'restaurant_id',
        'branch_id',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /** Ventana en la que una reserva marca la mesa como "reserved". */
    public const RESERVATION_HOLD_BEFORE_MINUTES = 120;

    public const RESERVATION_HOLD_AFTER_MINUTES = 30;

    /**
     * Recalcula el estado: occupied si tiene un pedido activo, reserved si tiene una reserva
     * pendiente/confirmada próxima (o recién vencida), available en otro caso.
     */
    public function syncStatus(): void
    {
        $status = 'available';

        if ($this->orders()->active()->exists()) {
            $status = 'occupied';
        } elseif ($this->reservations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('reservation_time', [
                now()->subMinutes(self::RESERVATION_HOLD_AFTER_MINUTES),
                now()->addMinutes(self::RESERVATION_HOLD_BEFORE_MINUTES),
            ])
            ->exists()) {
            $status = 'reserved';
        }

        if ($this->status !== $status) {
            $this->update(['status' => $status]);
        }
    }
}
