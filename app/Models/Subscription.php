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
        'mercadopago_subscription_id',
        'mercadopago_payer_id',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
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
