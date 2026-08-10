<?php

namespace App\Services;

use App\Contracts\GoogleIdTokenVerifier;
use App\Data\GoogleUserData;
use App\Enums\UserRole;
use App\Models\User;

class GoogleAuthService
{
    public function __construct(private GoogleIdTokenVerifier $verifier) {}

    /**
     * @throws \Exception
     */
    public function verifyIdToken(string $idToken): GoogleUserData
    {
        return $this->verifier->verify($idToken);
    }

    public function authenticate(GoogleUserData $googleUser): User
    {
        $user = User::query()->where('google_id', $googleUser->id)->first()
            ?? User::query()->where('email', $googleUser->email)->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->id,
            ])->save();

            if ($user->email_verified_at === null) {
                $user->markEmailAsVerified();
            }

            return $user;
        }

        $user = User::query()->create([
            'name' => $googleUser->name ?? $googleUser->email,
            'email' => $googleUser->email,
            'google_id' => $googleUser->id,
            'password' => null,
            'role' => UserRole::Customer,
        ]);

        $user->markEmailAsVerified();

        return $user;
    }
}
