<?php

namespace Database\Factories;

use App\Enums\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_category_id' => ProductCategory::factory(),
            'sku' => 'PRD-'.fake()->unique()->numerify('######'),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'brand' => fake()->optional()->company(),
            'unit' => fake()->randomElement(MeasurementUnit::cases()),
            'minimum_stock' => fake()->randomFloat(3, 1, 10),
            'current_stock' => fake()->randomFloat(3, 10, 100),
            'location' => fake()->optional()->bothify('A-##'),
            'last_purchase_cost' => fake()->randomFloat(4, 5, 500),
            'sale_price' => fake()->randomFloat(2, 10, 700),
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (): array => [
            'minimum_stock' => 10,
            'current_stock' => 5,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['current_stock' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
