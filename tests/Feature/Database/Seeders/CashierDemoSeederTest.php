<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\CashierDemoSeeder;
use Database\Seeders\InventoryDemoSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(InventoryDemoSeeder::class);
});

test('seeds an open register with demo sales only when the admin exists', function () {
    $this->seed(CashierDemoSeeder::class);
    expect(CashRegister::query()->count())->toBe(0);

    User::factory()->create(['email' => 'admin@fredyracing.test']);
    $this->seed(CashierDemoSeeder::class);
    $this->seed(CashierDemoSeeder::class);

    $register = CashRegister::query()->firstOrFail();
    expect(CashRegister::query()->count())->toBe(1)
        ->and($register->status)->toBe(CashRegisterStatus::Open)
        ->and(Sale::query()->count())->toBe(2)
        ->and((float) $register->summary()['expected_cash'])->toBe(205.5);
});
