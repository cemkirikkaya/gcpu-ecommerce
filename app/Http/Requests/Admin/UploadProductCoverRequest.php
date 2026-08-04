<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductCoverRequest extends FormRequest
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
            'image' => ['required', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Kapak görseli seçmelisiniz.',
            'image.image' => 'Yalnızca görsel dosyası yükleyebilirsiniz.',
            'image.max' => 'Görsel en fazla 5 MB olabilir.',
        ];
    }
}
