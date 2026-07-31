<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'catalog_variants' => ['required', 'array', 'min:1'],
            'catalog_variants.*.sku' => [
                'required',
                'string',
                'max:255',
                'distinct',
                Rule::unique('product_variants', 'sku')->whereNull('deleted_at'),
            ],
            'catalog_variants.*.stock' => ['required', 'integer', 'min:0'],
            'catalog_variants.*.color' => ['nullable', 'string', 'max:255'],
            'catalog_variants.*.memory' => ['nullable', 'string', 'max:255'],
            'catalog_variants.*.model' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Ürün adı zorunludur.',
            'price.required' => 'Fiyat zorunludur.',
            'catalog_variants.required' => 'En az bir varyant eklemelisiniz.',
            'catalog_variants.min' => 'En az bir varyant eklemelisiniz (SKU ve stok zorunlu).',
            'catalog_variants.*.sku.required' => 'Her varyant için benzersiz bir SKU kodu girin.',
            'catalog_variants.*.sku.distinct' => 'Aynı SKU kodunu birden fazla varyantta kullanamazsınız.',
            'catalog_variants.*.sku.unique' => 'Bu SKU kodu zaten kullanılıyor. Benzersiz bir kod girin (örn. HOOD-BLACK-M).',
            'catalog_variants.*.stock.required' => 'Stok miktarı zorunludur.',
        ];
    }
}
