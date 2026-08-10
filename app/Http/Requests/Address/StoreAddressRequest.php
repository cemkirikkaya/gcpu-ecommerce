<?php

namespace App\Http\Requests\Address;

class StoreAddressRequest extends AddressRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->addressRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->addressMessages();
    }
}
