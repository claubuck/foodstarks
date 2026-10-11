<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Reservation;
use App\Models\Table;

function posSetup(string $role = 'waiter'): array
{
    [$user, $branch] = restaurantUser($role);
    $category = ProductCategory::create(['restaurant_id' => $user->restaurant_id, 'name' => 'Platos']);
    $product = Product::create([
        'restaurant_id' => $user->restaurant_id,
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'name' => 'Milanesa',
        'price' => 100,
        'is_active' => true,
    ]);
    $table = Table::create([
        'restaurant_id' => $user->restaurant_id,
        'branch_id' => $branch->id,
        'table_number' => '1',
        'capacity' => 4,
        'status' => 'available',
    ]);

    return [$user, $branch, $product, $table];
}

test('dashboard and sales screen render for a new restaurant', function () {
    [$user] = posSetup();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('sales.create'))->assertOk();
    $this->actingAs($user)->get(route('orders.index'))->assertOk();
});

test('creating an order uses the database price and occupies the table', function () {
    [$user, , $product, $table] = posSetup();

    $this->actingAs($user)->post(route('orders.store'), [
        'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 1]],
        'type' => 'dine_in',
        'payment_method' => 'cash',
        'table_id' => $table->id,
    ])->assertRedirect(route('dashboard'));

    $order = Order::sole();
    expect((float) $order->total_amount)->toBe(200.0)
        ->and($order->orderDetails()->sole()->unit_price)->toEqual(100)
        ->and($table->fresh()->status)->toBe('occupied');
});

test('adding items to an open order only adds the new items', function () {
    [$user, , $product, $table] = posSetup();
    $this->actingAs($user);

    $this->post(route('orders.store'), [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
        'type' => 'dine_in', 'payment_method' => 'cash', 'table_id' => $table->id,
    ]);
    $order = Order::sole();

    $this->post(route('orders.store'), [
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
        'type' => 'dine_in', 'payment_method' => 'cash', 'table_id' => $table->id, 'order_id' => $order->id,
    ]);

    expect(Order::count())->toBe(1)
        ->and($order->orderDetails()->sum('quantity'))->toBe(3)
        ->and((float) $order->fresh()->total_amount)->toBe(300.0);
});

test('ordering for a table with an open order appends to it instead of creating another', function () {
    [$user, , $product, $table] = posSetup();
    $this->actingAs($user);

    foreach ([1, 1] as $qty) {
        $this->post(route('orders.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            'type' => 'dine_in', 'payment_method' => 'cash', 'table_id' => $table->id,
        ]);
    }

    expect(Order::count())->toBe(1)
        ->and((float) Order::sole()->total_amount)->toBe(200.0);
});

test('charging an order completes it, stores the payment method and frees the table', function () {
    [$user, , $product, $table] = posSetup();
    $this->actingAs($user)->post(route('orders.store'), [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
        'type' => 'dine_in', 'payment_method' => 'cash', 'table_id' => $table->id,
    ]);
    $order = Order::sole();

    $this->post(route('orders.cobrar', $order), ['payment_method' => 'card'])->assertRedirect(route('dashboard'));

    $order->refresh();
    expect($order->status)->toBe('completed')
        ->and($order->paid)->toBeTruthy()
        ->and($order->payment_method)->toBe('card')
        ->and($table->fresh()->status)->toBe('available');
});

test('an order can be deleted together with its items', function () {
    [$user, , $product, $table] = posSetup();
    $this->actingAs($user)->post(route('orders.store'), [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
        'type' => 'dine_in', 'payment_method' => 'cash', 'table_id' => $table->id,
    ]);

    $this->delete(route('orders.destroy', Order::sole()))->assertRedirect(route('orders.index'));

    expect(Order::count())->toBe(0)
        ->and($table->fresh()->status)->toBe('available');
});

test('products from another restaurant cannot be ordered', function () {
    [$user] = posSetup();
    [, , $foreignProduct] = posSetup();

    $this->actingAs($user)->post(route('orders.store'), [
        'items' => [['product_id' => $foreignProduct->id, 'quantity' => 1]],
        'type' => 'takeaway', 'payment_method' => 'cash',
    ])->assertSessionHasErrors('items.0.product_id');
});

test('a table with an open order cannot be deleted, a free one with past reservations can', function () {
    [$user, $branch, $product, $table] = posSetup('owner');
    $this->actingAs($user)->post(route('orders.store'), [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
        'type' => 'dine_in', 'payment_method' => 'cash', 'table_id' => $table->id,
    ]);

    $this->delete(route('tables.destroy', $table))->assertSessionHas('error');
    expect(Table::count())->toBe(1);

    $this->post(route('orders.cobrar', Order::sole()));
    $reservation = Reservation::create([
        'restaurant_id' => $user->restaurant_id, 'branch_id' => $branch->id, 'table_id' => $table->id,
        'customer_name' => 'Ana', 'customer_phone' => '1', 'reservation_time' => now()->subDay(),
        'party_size' => 2, 'status' => 'completed',
    ]);

    $this->delete(route('tables.destroy', $table))->assertSessionHas('success');
    expect(Table::count())->toBe(0)
        ->and($reservation->fresh()->table_id)->toBeNull();
});

test('a reservation only holds the table near its time and releases it when cancelled', function () {
    [$user, , , $table] = posSetup();
    $this->actingAs($user);

    $this->post(route('reservations.store'), [
        'table_id' => $table->id, 'customer_name' => 'Ana', 'customer_phone' => '1',
        'reservation_time' => now()->addDays(3)->toDateTimeString(), 'party_size' => 2,
    ]);
    expect($table->fresh()->status)->toBe('available');

    $this->post(route('reservations.store'), [
        'table_id' => $table->id, 'customer_name' => 'Juan', 'customer_phone' => '2',
        'reservation_time' => now()->addMinutes(30)->toDateTimeString(), 'party_size' => 2,
    ]);
    expect($table->fresh()->status)->toBe('reserved');

    $soon = Reservation::where('customer_name', 'Juan')->sole();
    $this->put(route('reservations.update', $soon), ['status' => 'cancelled']);
    expect($table->fresh()->status)->toBe('available');
});
