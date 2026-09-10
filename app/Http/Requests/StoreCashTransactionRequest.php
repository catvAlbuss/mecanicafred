<?php

namespace App\Http\Requests;

use App\Concerns\CashTransactionValidationRules;
use App\Models\CashRegister;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCashTransactionRequest extends FormRequest
{
    use CashTransactionValidationRules;

    public function authorize(): bool
    {
        $register = CashRegister::currentOpen();

        return $register !== null && ($this->user()?->can('registerTransaction', $register) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->cashTransactionRules();
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateCashTransactionCategory($validator);
    }
}
