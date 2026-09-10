<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tax_id' => fake()->unique()->numerify('20#########'),
            'business_name' => fake()->unique()->company(),
            'trade_name' => fake()->optional()->company(),
            'contact_name' => fake()->optional()->name(),
            'phone' => fake()->optional()->numerify('9########'),
            'secondary_phone' => fake()->optional()->numerify('9########'),
            'email' => fake()->optional()->companyEmail(),
            'address' => fake()->optional()->address(),
            'district' => fake()->optional()->city(),
            'province' => fake()->optional()->city(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
