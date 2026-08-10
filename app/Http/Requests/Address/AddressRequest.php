<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

abstract class AddressRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function addressRules(bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return [
            'title' => ['nullable', 'string', 'max:100'],
            'first_name' => [$rule, 'string', 'max:100'],
            'last_name' => [$rule, 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line_1' => [$rule, 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => [$rule, 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => [$rule, 'string', 'max:20'],
            'country' => [$rule, 'string', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function addressMessages(): array
    {
        return [
            'first_name.required' => 'Ad alanı zorunludur.',
            'last_name.required' => 'Soyad alanı zorunludur.',
            'address_line_1.required' => 'Adres satırı zorunludur.',
            'city.required' => 'Şehir alanı zorunludur.',
            'postal_code.required' => 'Posta kodu zorunludur.',
            'country.required' => 'Ülke alanı zorunludur.',
        ];
    }
}
