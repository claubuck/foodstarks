<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'restaurant_name' => 'La Esquina',
        'restaurant_address' => 'Av. Siempreviva 742',
        'restaurant_phone' => '1155555555',
        'restaurant_email' => 'contacto@laesquina.test',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    // Queda con sucursal y plan Free sin vencimiento, y puede entrar al panel
    $this->get(route('dashboard'))->assertOk();
});

test('registration rejects a restaurant email already in use', function () {
    \App\Models\Restaurant::factory()->create(['email' => 'repetido@test.com']);

    $this->post('/register', [
        'restaurant_name' => 'Otro',
        'restaurant_address' => 'Calle 1',
        'restaurant_phone' => '123',
        'restaurant_email' => 'repetido@test.com',
        'name' => 'Test User',
        'email' => 'nuevo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('restaurant_email');

    $this->assertGuest();
});
