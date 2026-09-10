<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idempotency_key' => fake()->uuid(),
            'status' => SaleStatus::Completed,
            'cash_register_id' => CashRegister::factory(),
            'sold_by' => User::factory(),
            'payment_method' => PaymentMethod::Cash,
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'sold_at' => now(),
        ];
    }
}
