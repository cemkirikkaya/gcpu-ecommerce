<?php

namespace App\Data;

readonly class GoogleUserData
{
    public function __construct(
        public string $id,
        public ?string $email,
        public ?string $name,
    ) {}
}
