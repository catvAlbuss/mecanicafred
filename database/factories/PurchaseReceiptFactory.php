<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReceipt>
 */
class PurchaseReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idempotency_key' => fake()->uuid(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'received_by' => User::factory(),
            'received_at' => now(),
            'supplier_document_number' => fake()->optional()->bothify('G-####'),
        ];
    }
}
