<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class InitIyzicoPaymentRequest extends FormRequest
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
            'installment' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function installment(): int
    {
        return (int) ($this->validated('installment') ?? 1);
    }
}
