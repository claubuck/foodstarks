<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckUserData extends Command
{
    protected $signature = 'user:check {email : Email del usuario}';

    protected $description = 'Verifica si un usuario tiene los datos necesarios (restaurant_id, sucursales) para usar la app';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No existe un usuario con email: {$email}");
            return self::FAILURE;
        }

        $this->info("Usuario: {$user->name} ({$user->email})");
        $this->table(
            ['Campo', 'Valor'],
            [
                ['id', $user->id],
                ['restaurant_id', $user->restaurant_id ?? '(null)'],
                ['role', $user->role ?? '(null)'],
            ]
        );

        if (! $user->restaurant_id) {
            $this->warn('⚠ El usuario NO tiene restaurant_id. Debe tener un restaurante asignado.');
            return self::FAILURE;
        }

        $restaurant = $user->restaurant;
        if (! $restaurant) {
            $this->warn('⚠ El restaurant_id no existe en la tabla restaurants.');
            return self::FAILURE;
        }

        $branches = $restaurant->branches()->where('is_active', true)->get(['id', 'name']);
        if ($branches->isEmpty()) {
            $this->warn('⚠ El restaurante no tiene ninguna sucursal activa. Se necesita al menos una.');
            $this->line('  Crea una sucursal desde Configuración → Sucursales, o ejecuta el seeder.');
            return self::FAILURE;
        }

        $this->info('Sucursales del restaurante:');
        $this->table(
            ['id', 'name'],
            $branches->map(fn ($b) => [$b->id, $b->name])->toArray()
        );

        $this->info('✓ El usuario tiene los datos necesarios. current_branch_id se establecerá con la primera sucursal.');
        return self::SUCCESS;
    }
}
