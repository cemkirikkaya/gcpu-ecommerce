<?php

namespace App\Http\Requests\Orders;

use App\Enums\ReturnRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ReturnRequestType::class)],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.replacement_product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Talep türü zorunludur.',
            'message.required' => 'İade veya değişim gerekçesi zorunludur.',
            'message.min' => 'Gerekçe en az 10 karakter olmalıdır.',
            'items.required' => 'En az bir ürün seçmelisiniz.',
            'items.min' => 'En az bir ürün seçmelisiniz.',
        ];
    }
}
