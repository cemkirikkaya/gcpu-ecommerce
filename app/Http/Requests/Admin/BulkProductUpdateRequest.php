<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkProductUpdateRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'CSV dosyası seçmelisiniz.',
            'file.mimes' => 'Yalnızca CSV dosyası yükleyebilirsiniz.',
            'file.max' => 'CSV dosyası en fazla 5 MB olabilir.',
        ];
    }
}
