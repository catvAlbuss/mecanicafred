<?php

namespace App\Concerns;

use App\Enums\ProductType;
use App\Models\ProductCategory;
use Illuminate\Validation\Rule;

trait ProductCategoryValidationRules
{
    /** @return array<string, mixed> */
    protected function productCategoryRules(?ProductCategory $category = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('product_categories', 'name')->ignore($category)],
            'slug' => ['required', 'string', 'max:120', Rule::unique('product_categories', 'slug')->ignore($category)],
            'type' => ['required', Rule::enum(ProductType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
