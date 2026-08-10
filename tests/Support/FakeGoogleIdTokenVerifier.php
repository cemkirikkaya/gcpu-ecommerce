<?php

namespace Tests\Support;

use App\Contracts\GoogleIdTokenVerifier;
use App\Data\GoogleUserData;

class FakeGoogleIdTokenVerifier implements GoogleIdTokenVerifier
{
    /**
     * @param  array<string, GoogleUserData|\Throwable>  $responses
     */
    public function __construct(private array $responses = []) {}

    public function verify(string $idToken): GoogleUserData
    {
        if (! array_key_exists($idToken, $this->responses)) {
            throw new \InvalidArgumentException("Unexpected Google ID token [{$idToken}].");
        }

        $response = $this->responses[$idToken];

        if ($response instanceof \Throwable) {
            throw $response;
        }

        return $response;
    }
}
