<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a customer through the api and stores the user in the database', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Yeni Müşteri',
        'email' => 'yeni@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'account_type' => 'customer',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('user.name', 'Yeni Müşteri')
        ->assertJsonPath('user.email', 'yeni@example.com')
        ->assertJsonPath('user.role', UserRole::Customer->value)
        ->assertJsonStructure(['token']);

    $this->assertDatabaseHas('users', [
        'email' => 'yeni@example.com',
        'role' => UserRole::Customer->value,
    ]);
});

it('logs in a customer through the api', function () {
    $user = User::factory()->create([
        'email' => 'musteri@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'musteri@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['token']);
});

it('allows admin users to log in through the api', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => '12345',
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'admin@example.com',
        'password' => '12345',
    ])
        ->assertOk()
        ->assertJsonPath('user.role', UserRole::Admin->value)
        ->assertJsonStructure(['token']);
});

it('rejects duplicate registration emails', function () {
    User::factory()->create(['email' => 'mevcut@example.com']);

    $this->postJson('/api/auth/register', [
        'name' => 'Başka Kullanıcı',
        'email' => 'mevcut@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'account_type' => 'customer',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
