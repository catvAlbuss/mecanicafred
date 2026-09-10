<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierInquiryItem>
 */
class SupplierInquiryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_inquiry_id' => SupplierInquiry::factory(),
            'product_id' => Product::factory(),
            'quantity_requested' => fake()->randomFloat(3, 1, 20),
        ];
    }

    public function available(): static
    {
        return $this->state(fn () => ['is_available' => true, 'quantity_available' => 10, 'quoted_unit_cost' => 25.5]);
    }
}
