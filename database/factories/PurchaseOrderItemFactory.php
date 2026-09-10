<?php

namespace Database\Factories;

use App\Enums\MeasurementUnit;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'product_sku' => 'PRD-'.fake()->unique()->numerify('######'),
            'unit' => MeasurementUnit::Unit,
            'quantity_ordered' => 5,
            'quantity_received' => 0,
            'unit_cost' => 25.5,
            'subtotal' => 127.5,
        ];
    }
}
