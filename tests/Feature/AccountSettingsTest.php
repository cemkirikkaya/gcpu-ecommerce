<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('updates the authenticated user profile', function () {
    $user = User::factory()->create([
        'name' => 'Eski Ad',
        'email' => 'eski@example.com',
        'password' => 'password123',
    ]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->putJson('/api/auth/profile', [
            'name' => 'Yeni Ad',
            'email' => 'yeni@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('user.name', 'Yeni Ad')
        ->assertJsonPath('user.email', 'yeni@example.com');

    expect($user->fresh())
        ->name->toBe('Yeni Ad')
        ->email->toBe('yeni@example.com');
});

it('rejects profile updates with duplicate emails', function () {
    User::factory()->create(['email' => 'mevcut@example.com']);

    $user = User::factory()->create(['email' => 'benim@example.com']);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->putJson('/api/auth/profile', [
            'name' => 'Ad',
            'email' => 'mevcut@example.com',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('updates the password when the current password is correct', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->putJson('/api/auth/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertOk()
        ->assertJsonPath('user.has_password', true);

    $this->assertTrue(Hash::check('new-password-123', (string) $user->fresh()->password));
});

it('rejects password updates with an incorrect current password', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->putJson('/api/auth/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

it('allows google users to set a password without a current password', function () {
    $user = User::factory()->create([
        'password' => null,
        'google_id' => 'google-user-123',
    ]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->putJson('/api/auth/password', [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertOk()
        ->assertJsonPath('user.has_password', true);

    $this->assertTrue($user->fresh()->hasPassword());
});

it('sends a password reset notification for forgot password requests', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'musteri@example.com']);

    $this->postJson('/api/auth/forgot-password', [
        'email' => 'musteri@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create([
        'email' => 'musteri@example.com',
        'password' => 'old-password-123',
    ]);

    /** @var PasswordBroker $broker */
    $broker = Password::broker();
    $token = $broker->createToken($user);

    $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'musteri@example.com',
        'password' => 'reset-password-123',
        'password_confirmation' => 'reset-password-123',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Şifreniz sıfırlandı. Yeni şifrenizle giriş yapabilirsiniz.');

    $this->assertTrue(Hash::check('reset-password-123', (string) $user->fresh()->password));
});

it('rejects password reset with an invalid token', function () {
    User::factory()->create(['email' => 'musteri@example.com']);

    $this->postJson('/api/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => 'musteri@example.com',
        'password' => 'reset-password-123',
        'password_confirmation' => 'reset-password-123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Sıfırlama bağlantısı geçersiz veya süresi dolmuş.');
});

it('requires authentication for profile and password updates', function () {
    $this->putJson('/api/auth/profile', [
        'name' => 'Ad',
        'email' => 'ad@example.com',
    ])->assertUnauthorized();

    $this->putJson('/api/auth/password', [
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertUnauthorized();
});
