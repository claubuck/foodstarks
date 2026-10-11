<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Restaurante listo para operar: sucursal activa + plan (Free por defecto) + usuario con el rol pedido.
 * Devuelve [user, branch].
 */
function restaurantUser(string $role = 'owner', string $plan = 'free'): array
{
    (new Database\Seeders\SubscriptionPlanSeeder)->run();

    $restaurant = App\Models\Restaurant::factory()->create();
    $branch = App\Models\Branch::create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Casa matriz',
        'is_active' => true,
    ]);
    $restaurant->assignPlan(App\Models\SubscriptionPlan::where('slug', $plan)->firstOrFail());

    $user = App\Models\User::factory()->create(['restaurant_id' => $restaurant->id, 'role' => $role]);

    return [$user, $branch];
}
