<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Database\Seeders\InventoryDemoSeeder;
use Database\Seeders\PurchaseOrderSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(InventoryDemoSeeder::class);
});

test('seeds one confirmed demo order only when the admin user exists', function () {
    $this->seed(PurchaseOrderSeeder::class);

    expect(PurchaseOrder::query()->count())->toBe(0);

    User::factory()->create(['email' => 'admin@fredyracing.test']);
    $this->seed(PurchaseOrderSeeder::class);
    $this->seed(PurchaseOrderSeeder::class);

    $order = PurchaseOrder::query()->firstOrFail();
    expect(PurchaseOrder::query()->count())->toBe(1)
        ->and($order->status)->toBe(PurchaseOrderStatus::Confirmed)
        ->and($order->number)->toMatch('/^OC-\d{4}-\d{6}$/')
        ->and($order->items)->toHaveCount(3);
});
