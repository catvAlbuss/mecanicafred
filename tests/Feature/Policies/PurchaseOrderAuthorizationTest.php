<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('allows receivers only on confirmed or partially received orders', function () {
    $receiver = User::factory()->create();
    $receiver->givePermissionTo('pedidos-compra.recibir');
    $confirmed = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Confirmed]);
    $partial = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::PartiallyReceived]);
    $draft = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Draft]);
    $received = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Received]);

    expect($receiver->can('receive', $confirmed))->toBeTrue()
        ->and($receiver->can('receive', $partial))->toBeTrue()
        ->and($receiver->can('receive', $draft))->toBeFalse()
        ->and($receiver->can('receive', $received))->toBeFalse();
});

test('forbids receiving when the permission is missing', function () {
    $user = User::factory()->create();
    $order = PurchaseOrder::factory()->create(['created_by' => $user, 'status' => PurchaseOrderStatus::Confirmed]);

    expect($user->can('receive', $order))->toBeFalse();
});
