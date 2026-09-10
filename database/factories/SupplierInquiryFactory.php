<?php

namespace Database\Factories;

use App\Enums\SupplierInquiryStatus;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierInquiry>
 */
class SupplierInquiryFactory extends Factory
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
            'requested_by' => User::factory(),
            'status' => SupplierInquiryStatus::Draft,
            'valid_until' => now()->addDays(15),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => ['status' => SupplierInquiryStatus::Sent, 'requested_at' => now()]);
    }

    public function answered(): static
    {
        return $this->state(fn () => ['status' => SupplierInquiryStatus::Answered, 'requested_at' => now()->subDay(), 'responded_at' => now()]);
    }
}
