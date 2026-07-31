<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
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
        return [
            'address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()?->id),
                ),
            ],
            'first_name' => ['required_without:address_id', 'string', 'max:100'],
            'last_name' => ['required_without:address_id', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line_1' => ['required_without:address_id', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required_without:address_id', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['required_without:address_id', 'string', 'max:20'],
            'country' => ['required_without:address_id', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required_without' => 'Ad alanı zorunludur.',
            'last_name.required_without' => 'Soyad alanı zorunludur.',
            'address_line_1.required_without' => 'Adres satırı zorunludur.',
            'city.required_without' => 'Şehir alanı zorunludur.',
            'postal_code.required_without' => 'Posta kodu zorunludur.',
            'country.required_without' => 'Ülke alanı zorunludur.',
        ];
    }
}
