<?php

namespace App\Contracts;

use App\Data\GoogleUserData;

interface GoogleIdTokenVerifier
{
    /**
     * @throws \Exception
     */
    public function verify(string $idToken): GoogleUserData;
}
