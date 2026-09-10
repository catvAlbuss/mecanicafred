<?php

namespace Database\Factories;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegister>
 */
class CashRegisterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => CashRegisterStatus::Open,
            'opened_by' => User::factory(),
            'opening_amount' => fake()->randomFloat(2, 50, 300),
            'opened_at' => now(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => CashRegisterStatus::Closed,
            'closed_by' => User::factory(),
            'closed_at' => now(),
            'expected_cash_amount' => 0,
            'counted_cash_amount' => 0,
            'difference' => 0,
        ]);
    }
}
