<?php

use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;

test('free plan without end date gives access; an expired plan does not', function () {
    [$user] = restaurantUser();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    Subscription::query()->update(['current_period_end' => now()->subDay()]);
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('subscription.required'));
    $this->actingAs($user)->get(route('subscription.required'))->assertOk();
});

test('owner can create, edit and delete employees within the plan limit', function () {
    [$owner] = restaurantUser('owner');
    $this->actingAs($owner);

    $this->post(route('employees.store'), [
        'name' => 'Mozo', 'email' => 'mozo@test.com', 'role' => 'waiter',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHas('success');
    $employee = User::where('email', 'mozo@test.com')->sole();

    $this->put(route('employees.update', $employee), [
        'name' => 'Mozo Jefe', 'email' => 'mozo@test.com', 'role' => 'manager',
    ])->assertSessionHas('success');
    expect($employee->fresh()->role)->toBe('manager');

    $this->delete(route('employees.destroy', $employee))->assertSessionHas('success');
    expect(User::find($employee->id))->toBeNull();

    // El dueño no se gestiona desde empleados
    $this->delete(route('employees.destroy', $owner))->assertForbidden();
});

test('employee limit comes from the current plan', function () {
    [$owner] = restaurantUser('owner');
    $max = SubscriptionPlan::where('slug', 'free')->value('max_users');
    User::factory()->count($max - 1)->create(['restaurant_id' => $owner->restaurant_id]);

    $this->actingAs($owner)->post(route('employees.store'), [
        'name' => 'Extra', 'email' => 'extra@test.com', 'role' => 'waiter',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHas('error');
});

test('waiters cannot manage employees or settings', function () {
    [$waiter] = restaurantUser('waiter');

    $this->actingAs($waiter)->get(route('employees.index'))->assertForbidden();
    $this->actingAs($waiter)->get(route('restaurant.edit'))->assertForbidden();
});

test('superadmin panel is only for SUPERADMIN_EMAILS and can assign plans', function () {
    [$owner] = restaurantUser('owner');
    $this->actingAs($owner)->get(route('superadmin.dashboard'))->assertForbidden();

    config(['app.superadmins' => [$owner->email]]);
    $this->actingAs($owner)->get(route('superadmin.dashboard'))->assertOk();

    $restaurant = Restaurant::find($owner->restaurant_id);
    $plan = SubscriptionPlan::where('slug', 'profesional')->sole();
    $this->post(route('superadmin.restaurants.plan', $restaurant), ['plan_id' => $plan->id, 'days' => 30])
        ->assertSessionHas('success');

    expect($restaurant->currentPlan()->slug)->toBe('profesional')
        ->and($restaurant->subscriptions()->current()->count())->toBe(1);
});

test('diagnostic routes are not public', function () {
    $this->get('/superadmin/queue-test')->assertRedirect(route('login'));
    $this->get('/queue-test')->assertNotFound();
    $this->get('/reverb-diagnostic')->assertNotFound();
});

test('subscription:activate assigns the requested plan', function () {
    [$owner] = restaurantUser('owner');

    $this->artisan('subscription:activate', ['email' => $owner->email, '--plan' => 'basico', '--days' => 30])
        ->assertSuccessful();

    expect(Restaurant::find($owner->restaurant_id)->currentPlan()->slug)->toBe('basico');
});

test('the owner cannot delete their own account', function () {
    [$owner] = restaurantUser('owner');

    $this->actingAs($owner)->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('profile.edit'));

    expect(User::find($owner->id))->not->toBeNull();
});

test('superadmin sees users and restaurant detail and can register payments', function () {
    [$owner] = restaurantUser('owner');
    config(['app.superadmins' => [$owner->email]]);
    $restaurant = Restaurant::find($owner->restaurant_id);
    $plan = SubscriptionPlan::where('slug', 'basico')->sole();

    $this->actingAs($owner)
        ->post(route('superadmin.restaurants.plan', $restaurant), ['plan_id' => $plan->id, 'days' => 30])
        ->assertSessionHas('success');
    $subscription = $restaurant->activeSubscription()->with('plan')->first();
    expect($subscription->paymentStatus())->toBe('pending');

    $this->get(route('superadmin.dashboard', ['status' => 'pending', 'search' => strtoupper($restaurant->name)]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('restaurants.total', 1)->where('stats.pending_payment', 1));

    $this->post(route('superadmin.subscriptions.payment', $subscription), ['paid' => true, 'payment_reference' => 'TRF-1'])
        ->assertSessionHas('success');
    expect($subscription->fresh()->paid_at)->not->toBeNull()
        ->and((float) $subscription->fresh()->amount_paid)->toBe((float) $plan->price_monthly);

    $this->get(route('superadmin.restaurants.show', $restaurant))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Superadmin/Restaurant')->where('subscriptions.0.payment_status', 'paid'));
    $this->get(route('superadmin.users', ['role' => 'owner']))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Superadmin/Users')->where('users.total', 1));
});

test('login records last_login', function () {
    [$owner] = restaurantUser('owner');
    $this->post(route('login'), ['email' => $owner->email, 'password' => 'password']);
    expect($owner->fresh()->last_login)->not->toBeNull();
});
