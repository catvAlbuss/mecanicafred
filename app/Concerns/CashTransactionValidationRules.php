<?php

namespace App\Concerns;

use App\Enums\CashTransactionCategory;
use App\Enums\CashTransactionType;
use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait CashTransactionValidationRules
{
    /** @return array<string, mixed> */
    protected function cashTransactionRules(): array
    {
        return [
            'type' => ['required', Rule::enum(CashTransactionType::class)],
            'category' => ['required', Rule::enum(CashTransactionCategory::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'description' => ['required', 'string', 'max:200'],
            'reference' => ['nullable', 'string', 'max:150'],
        ];
    }

    protected function validateCashTransactionCategory(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $category = CashTransactionCategory::tryFrom((string) $this->input('category'));
            $type = CashTransactionType::tryFrom((string) $this->input('type'));

            if ($category === null || $type === null) {
                return;
            }

            if (! $category->isManuallySelectable()) {
                $validator->errors()->add('category', 'Esa categoría se genera automáticamente.');

                return;
            }

            if (! in_array($type, $category->allowedTypes(), true)) {
                $validator->errors()->add('category', 'La categoría no corresponde al tipo de movimiento.');
            }
        });
    }
}
