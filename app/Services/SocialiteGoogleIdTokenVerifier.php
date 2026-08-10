<?php

namespace App\Services;

use App\Contracts\GoogleIdTokenVerifier;
use App\Data\GoogleUserData;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;

class SocialiteGoogleIdTokenVerifier implements GoogleIdTokenVerifier
{
    public function __construct(private SocialiteFactory $socialite) {}

    public function verify(string $idToken): GoogleUserData
    {
        $googleUser = $this->socialite->driver('google')
            ->stateless()
            ->userFromToken($idToken);

        return new GoogleUserData(
            id: (string) $googleUser->getId(),
            email: $googleUser->getEmail(),
            name: $googleUser->getName(),
        );
    }
}
