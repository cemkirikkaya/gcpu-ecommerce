<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
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
        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        if ($this->user()?->hasPassword()) {
            $rules['current_password'] = ['required', 'string'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Mevcut şifrenizi girmelisiniz.',
            'password.required' => 'Yeni şifre alanı zorunludur.',
            'password.confirmed' => 'Yeni şifreler eşleşmiyor.',
        ];
    }
}
