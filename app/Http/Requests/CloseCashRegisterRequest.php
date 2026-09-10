<?php

namespace App\Http\Requests;

use App\Models\CashRegister;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CloseCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $register = CashRegister::currentOpen();

        return $register !== null && ($this->user()?->can('close', $register) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'counted_cash_amount' => ['required', 'decimal:0,2', 'gte:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
