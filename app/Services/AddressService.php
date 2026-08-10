<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;

class AddressService
{
    public function create(User $user, array $data): Address
    {
        $isDefault = (bool) ($data['is_default'] ?? false);
        $hasAddresses = $user->addresses()->exists();

        if (! $hasAddresses) {
            $isDefault = true;
        }

        if ($isDefault) {
            $this->clearDefaultFor($user);
        }

        return $user->addresses()->create([
            ...$data,
            'is_default' => $isDefault,
        ]);
    }

    public function update(Address $address, array $data): Address
    {
        if (($data['is_default'] ?? false) === true) {
            $this->clearDefaultFor($address->user);
        }

        $address->update($data);

        return $address->fresh();
    }

    public function delete(Address $address): void
    {
        $user = $address->user;
        $wasDefault = $address->is_default;

        $address->delete();

        if ($wasDefault) {
            $nextDefault = $user->addresses()->first();

            if ($nextDefault !== null) {
                $nextDefault->update(['is_default' => true]);
            }
        }
    }

    public function setDefault(Address $address): Address
    {
        $this->clearDefaultFor($address->user);
        $address->update(['is_default' => true]);

        return $address->fresh();
    }

    private function clearDefaultFor(User $user): void
    {
        $user->addresses()->update(['is_default' => false]);
    }
}
