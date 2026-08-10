<?php

namespace App\Http\Requests\Address;

class UpdateAddressRequest extends AddressRequest
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
        return $this->addressRules(required: false);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->addressMessages();
    }
}
