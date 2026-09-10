<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'created_by' => User::factory(),
            'status' => PurchaseOrderStatus::Draft,
            'currency' => 'PEN',
            'subtotal' => 0,
            'tax_rate' => 18,
            'tax' => 0,
            'total' => 0,
        ];
    }
}
