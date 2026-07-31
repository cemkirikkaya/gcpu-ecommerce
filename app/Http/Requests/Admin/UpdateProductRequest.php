<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'catalog_variants' => ['sometimes', 'array', 'min:1'],
            'catalog_variants.*.sku' => [
                'required_with:catalog_variants',
                'string',
                'max:255',
                'distinct',
                Rule::unique('product_variants', 'sku')
                    ->whereNull('deleted_at')
                    ->where(
                        fn ($query) => $query->where('product_id', '!=', $product->id),
                    ),
            ],
            'catalog_variants.*.stock' => ['required_with:catalog_variants', 'integer', 'min:0'],
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
            'catalog_variants.min' => 'En az bir varyant eklemelisiniz (SKU ve stok zorunlu).',
            'catalog_variants.*.sku.distinct' => 'Aynı SKU kodunu birden fazla varyantta kullanamazsınız.',
            'catalog_variants.*.sku.unique' => 'Bu SKU kodu başka bir üründe zaten kullanılıyor.',
        ];
    }
}
