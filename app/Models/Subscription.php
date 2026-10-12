<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'paid_at',
        'amount_paid',
        'payment_reference',
        'mercadopago_subscription_id',
        'mercadopago_payer_id',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'paid_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /** Vigentes: trial/active y sin fecha fin (Free) o con fecha fin futura. */
    public function scopeCurrent($query)
    {
        return $query->whereIn('status', ['trial', 'active'])
            ->where(fn ($q) => $q->whereNull('current_period_end')->orWhere('current_period_end', '>=', now()));
    }

    /** Estado de cobro del periodo: free (plan sin costo), paid o pending. Requiere `plan` cargado. */
    public function paymentStatus(): string
    {
        if ((float) $this->plan?->price_monthly <= 0) {
            return 'free';
        }

        return $this->paid_at ? 'paid' : 'pending';
    }

    /** Vigentes de planes pagos sin pago registrado. */
    public function scopeUnpaid($query)
    {
        return $query->whereNull('paid_at')->whereHas('plan', fn ($q) => $q->where('price_monthly', '>', 0));
    }

    public function isActive(): bool
    {
        if (! in_array($this->status, ['trial', 'active'])) {
            return false;
        }
        // Plan Free (o sin fecha fin): periodo ilimitado
        if ($this->current_period_end === null) {
            return true;
        }
        return $this->current_period_end->isFuture();
    }
}
