<?php

namespace App\Concerns;

use App\Enums\MeasurementUnit;
use App\Models\Product;
use Illuminate\Validation\Rule;

trait ProductValidationRules
{
    /** @return array<string, mixed> */
    protected function productRules(?Product $product = null, bool $creating = false): array
    {
        return [
            'product_category_id' => ['required', 'integer', Rule::exists('product_categories', 'id')->where('is_active', true)],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'barcode' => ['nullable', 'string', 'max:32', Rule::unique('products', 'barcode')->ignore($product)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'brand' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', Rule::enum(MeasurementUnit::class)],
            'minimum_stock' => ['required', 'decimal:0,3', 'min:0', 'max:99999999999.999'],
            'initial_stock' => $creating ? ['required', 'decimal:0,3', 'min:0', 'max:99999999999.999'] : ['prohibited'],
            'current_stock' => ['prohibited'],
            'location' => ['nullable', 'string', 'max:100'],
            'last_purchase_cost' => ['nullable', 'decimal:0,4', 'min:0', 'max:9999999999.9999'],
            'sale_price' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'is_active' => ['required', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
