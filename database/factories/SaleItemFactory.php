<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 5);
        $unitPrice = fake()->randomFloat(2, 5, 90);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'product_sku' => 'PRD-'.fake()->unique()->numerify('######'),
            'unit' => 'unit',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'unit_cost' => fake()->randomFloat(4, 1, 50),
            'subtotal' => round($quantity * $unitPrice, 2),
        ];
    }
}
