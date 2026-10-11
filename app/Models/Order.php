<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'branch_id',
        'table_id',
        'type',
        'total_amount',
        'status',
        'payment_method',
        'paid',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public const ACTIVE_STATUSES = ['pending', 'in_progress'];

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /** Recalcula total_amount a partir de los detalles. */
    public function recalculateTotal(): void
    {
        $this->update([
            'total_amount' => $this->orderDetails()->get()->sum(fn ($d) => $d->unit_price * $d->quantity),
        ]);
    }
}
