<?php

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the authenticated customers addresses', function () {
    $user = User::factory()->create();
    Address::factory()->count(2)->create(['user_id' => $user->id]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->getJson('/api/addresses')
        ->assertOk()
        ->assertJsonCount(2, 'addresses');
});

it('creates an address and marks the first one as default', function () {
    $user = User::factory()->create();

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson('/api/addresses', [
            'title' => 'Ev',
            'first_name' => 'Ayşe',
            'last_name' => 'Yılmaz',
            'phone' => '05551234567',
            'address_line_1' => 'Bağdat Caddesi No: 1',
            'city' => 'İstanbul',
            'postal_code' => '34710',
            'country' => 'Türkiye',
        ])
        ->assertCreated()
        ->assertJsonPath('address.is_default', true)
        ->assertJsonPath('address.full_name', 'Ayşe Yılmaz');
});

it('updates an owned address', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id, 'city' => 'Ankara']);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->putJson("/api/addresses/{$address->id}", [
            'city' => 'İzmir',
        ])
        ->assertOk()
        ->assertJsonPath('address.city', 'İzmir');
});

it('sets a new default address', function () {
    $user = User::factory()->create();
    $first = Address::factory()->default()->create(['user_id' => $user->id]);
    $second = Address::factory()->create(['user_id' => $user->id]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->patchJson("/api/addresses/{$second->id}/default")
        ->assertOk()
        ->assertJsonPath('address.is_default', true);

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue();
});

it('deletes an owned address and promotes another default', function () {
    $user = User::factory()->create();
    $first = Address::factory()->default()->create(['user_id' => $user->id]);
    $second = Address::factory()->create(['user_id' => $user->id]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->deleteJson("/api/addresses/{$first->id}")
        ->assertOk();

    expect(Address::query()->find($first->id))->toBeNull()
        ->and($second->fresh()->is_default)->toBeTrue();
});

it('forbids updating another users address', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $owner->id]);

    $this->withToken($other->createToken('test')->plainTextToken)
        ->putJson("/api/addresses/{$address->id}", [
            'city' => 'Bursa',
        ])
        ->assertForbidden();
});
