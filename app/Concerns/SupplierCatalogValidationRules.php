<?php

namespace App\Concerns;

use App\Enums\SupplierAvailabilityStatus;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Validation\Rule;

trait SupplierCatalogValidationRules
{
    /** @return array<string, mixed> */
    protected function catalogRules(Supplier $supplier, ?Product $product = null): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true), Rule::unique('product_supplier', 'product_id')->where('supplier_id', $supplier->id)->ignore($product?->id, 'product_id')],
            'supplier_sku' => ['nullable', 'string', 'max:100'],
            'last_unit_cost' => ['nullable', 'decimal:0,4', 'min:0', 'max:9999999999.9999'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'availability_status' => ['required', Rule::enum(SupplierAvailabilityStatus::class)],
            'available_quantity' => ['nullable', 'decimal:0,3', 'min:0', 'max:99999999999.999'],
            'is_preferred' => ['required', 'boolean'],
        ];
    }
}
