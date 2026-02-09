<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Console\Command;

class ActivateSubscriptionForUser extends Command
{
    protected $signature = 'subscription:activate {email : Email del usuario (ej: admin@gmail.com)} {--days=30 : Días de vigencia de la suscripción}';

    protected $description = 'Activa una suscripción de prueba para el restaurante del usuario indicado (para pruebas).';

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

        $restaurant = $user->restaurant;
        $plan = SubscriptionPlan::where('is_active', true)->first();

        if (! $plan) {
            $this->error('No hay ningún plan de suscripción activo. Ejecuta: php artisan db:seed --class=SubscriptionPlanSeeder');
            return self::FAILURE;
        }

        $existing = $restaurant->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->where('current_period_end', '>=', now())
            ->latest()
            ->first();

        if ($existing) {
            $existing->update([
                'current_period_end' => now()->addDays($days),
                'status' => 'active',
            ]);
            $this->info("Suscripción ya existente actualizada. Vigente hasta: {$existing->current_period_end->toDateString()}");
            return self::SUCCESS;
        }

        Subscription::create([
            'restaurant_id' => $restaurant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addDays($days),
        ]);

        $this->info("Suscripción activada para el restaurante de {$email}. Vigente por {$days} días.");
        return self::SUCCESS;
    }
}
