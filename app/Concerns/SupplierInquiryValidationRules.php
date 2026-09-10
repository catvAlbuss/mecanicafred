<?php

namespace App\Concerns;

use Illuminate\Validation\Rule;

trait SupplierInquiryValidationRules
{
    /** @return array<string, mixed> */
    protected function inquiryRules(): array
    {
        $supplierId = $this->integer('supplier_id');

        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('product_supplier', 'product_id')->where('supplier_id', $supplierId)],
            'items.*.quantity_requested' => ['required', 'decimal:0,3', 'gt:0', 'max:99999999999.999'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'extensions:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
