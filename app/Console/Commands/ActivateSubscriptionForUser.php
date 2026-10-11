<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Console\Command;

class ActivateSubscriptionForUser extends Command
{
    protected $signature = 'subscription:activate
        {email : Email de un usuario del restaurante}
        {--plan=basico : Slug del plan (free, basico, profesional, empresa)}
        {--days=30 : Días de vigencia (0 = sin vencimiento)}';

    protected $description = 'Asigna un plan al restaurante del usuario indicado (reemplaza la suscripción vigente).';

    public function handle(): int
    {
        $email = $this->argument('email');
        $days = (int) $this->option('days');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No se encontró ningún usuario con email: {$email}");

            return self::FAILURE;
        }

        if (! $user->restaurant_id) {
            $this->error("El usuario {$email} no tiene restaurante asociado.");

            return self::FAILURE;
        }

        $plan = SubscriptionPlan::where('slug', $this->option('plan'))->where('is_active', true)->first();

        if (! $plan) {
            $this->error("No existe el plan activo '{$this->option('plan')}'. Ejecuta: php artisan db:seed --class=SubscriptionPlanSeeder");

            return self::FAILURE;
        }

        $subscription = $user->restaurant->assignPlan($plan, $days > 0 ? $days : null);

        $this->info("Plan {$plan->name} activado para el restaurante de {$email}. Vence: "
            .($subscription->current_period_end?->toDateString() ?? 'nunca'));

        return self::SUCCESS;
    }
}
