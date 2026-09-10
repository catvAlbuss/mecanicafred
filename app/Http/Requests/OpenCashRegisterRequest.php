<?php

namespace App\Http\Requests;

use App\Models\CashRegister;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OpenCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('open', CashRegister::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'opening_amount' => ['required', 'decimal:0,2', 'gte:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
