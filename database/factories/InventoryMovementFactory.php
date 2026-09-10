<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $before = fake()->randomFloat(3, 1, 50);
        $quantity = fake()->randomFloat(3, 1, 10);

        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'type' => InventoryMovementType::AdjustmentIn,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $before + $quantity,
            'reason' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }
}
