<?php

namespace App\Concerns;

use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;

trait SaleValidationRules
{
    /** @return array<string, mixed> */
    protected function saleRules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_document' => ['nullable', 'string', 'max:20'],
            'discount' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0', 'max:99999999999.999'],
            'items.*.unit_price' => ['required', 'decimal:0,2', 'gte:0', 'max:9999999999.99'],
        ];
    }
}
