<?php

namespace Database\Factories;

use App\Enums\CashTransactionCategory;
use App\Enums\CashTransactionType;
use App\Enums\PaymentMethod;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashTransaction>
 */
class CashTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cash_register_id' => CashRegister::factory(),
            'user_id' => User::factory(),
            'type' => CashTransactionType::Expense,
            'category' => CashTransactionCategory::OperatingExpense,
            'payment_method' => PaymentMethod::Cash,
            'amount' => fake()->randomFloat(2, 5, 200),
            'description' => fake()->sentence(3),
            'occurred_at' => now(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (): array => [
            'type' => CashTransactionType::Income,
            'category' => CashTransactionCategory::CashDeposit,
        ]);
    }
}
