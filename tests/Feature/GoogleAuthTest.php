<?php

use App\Contracts\GoogleIdTokenVerifier;
use App\Data\GoogleUserData;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeGoogleIdTokenVerifier;

uses(RefreshDatabase::class);

function fakeGoogleIdTokenVerifier(array $responses): void
{
    app()->instance(
        GoogleIdTokenVerifier::class,
        new FakeGoogleIdTokenVerifier($responses),
    );
}

it('creates a customer through google login', function () {
    fakeGoogleIdTokenVerifier([
        'valid-google-id-token' => new GoogleUserData(
            id: 'google-user-123',
            email: 'google@example.com',
            name: 'Google Kullanıcı',
        ),
    ]);

    $response = $this->postJson('/api/auth/google', [
        'id_token' => 'valid-google-id-token',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('user.email', 'google@example.com')
        ->assertJsonPath('user.role', UserRole::Customer->value)
        ->assertJsonStructure(['token']);

    $this->assertDatabaseHas('users', [
        'email' => 'google@example.com',
        'google_id' => 'google-user-123',
        'role' => UserRole::Customer->value,
    ]);

    expect(User::query()->where('email', 'google@example.com')->first())
        ->password->toBeNull()
        ->email_verified_at->not->toBeNull();
});

it('links google login to an existing user with the same email', function () {
    $user = User::factory()->create([
        'email' => 'google@example.com',
        'google_id' => null,
        'email_verified_at' => null,
    ]);

    fakeGoogleIdTokenVerifier([
        'valid-google-id-token' => new GoogleUserData(
            id: 'google-user-123',
            email: 'google@example.com',
            name: 'Google Kullanıcı',
        ),
    ]);

    $this->postJson('/api/auth/google', [
        'id_token' => 'valid-google-id-token',
    ])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);

    expect($user->fresh())
        ->google_id->toBe('google-user-123')
        ->email_verified_at->not->toBeNull();
});

it('logs in an existing google user', function () {
    $user = User::factory()->create([
        'email' => 'google@example.com',
        'google_id' => 'google-user-123',
    ]);

    fakeGoogleIdTokenVerifier([
        'valid-google-id-token' => new GoogleUserData(
            id: 'google-user-123',
            email: 'google@example.com',
            name: 'Google Kullanıcı',
        ),
    ]);

    $this->postJson('/api/auth/google', [
        'id_token' => 'valid-google-id-token',
    ])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['token']);
});

it('rejects google login without an id token', function () {
    $this->postJson('/api/auth/google', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id_token']);
});

it('rejects invalid google id tokens', function () {
    fakeGoogleIdTokenVerifier([
        'invalid-google-id-token' => new Exception('Invalid token'),
    ]);

    $this->postJson('/api/auth/google', [
        'id_token' => 'invalid-google-id-token',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Google ile giriş doğrulanamadı.');
});

it('rejects google accounts without an email address', function () {
    fakeGoogleIdTokenVerifier([
        'valid-google-id-token' => new GoogleUserData(
            id: 'google-user-123',
            email: null,
            name: 'Google Kullanıcı',
        ),
    ]);

    $this->postJson('/api/auth/google', [
        'id_token' => 'valid-google-id-token',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Google hesabınızda e-posta adresi bulunamadı.');
});
