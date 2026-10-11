<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    /** @use HasFactory<\Database\Factories\RestaurantFactory> */
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'address',
        'phone',
        'email',
        'logo_url',
        'opening_hours',
    ];
    protected $casts = [
        'opening_hours' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function productCategories()
    {
        return $this->hasMany(ProductCategory::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function tables()
    {
        return $this->hasMany(Table::class);
    }
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->current()->latest('id');
    }

    /**
     * Reemplaza la suscripción vigente por una nueva del plan indicado.
     * $days null = sin vencimiento (lo normal para el plan Free).
     */
    public function assignPlan(SubscriptionPlan $plan, ?int $days = null): Subscription
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($plan, $days) {
            $this->subscriptions()->current()->update(['status' => 'cancelled']);

            return $this->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => $days ? now()->addDays($days) : null,
            ]);
        });
    }

    /** Plan de la suscripción vigente (null si no hay). */
    public function currentPlan(): ?SubscriptionPlan
    {
        return $this->activeSubscription()->with('plan')->first()?->plan;
    }
}
