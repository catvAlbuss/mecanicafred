<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('sends and confirms a purchase order with separated permissions', function () {
    $receptionist = User::factory()->create();
    $receptionist->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.crear']);
    $admin = User::factory()->create();
    $admin->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.aprobar', 'pedidos-compra.cancelar']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receptionist]);

    $this->actingAs($receptionist)->patch(route('purchases.orders.status.update', $order), ['action' => 'send'])->assertRedirect();
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Sent)->and($order->fresh()->ordered_at)->not->toBeNull();
    $this->actingAs($receptionist)->patch(route('purchases.orders.status.update', $order), ['action' => 'confirm'])->assertForbidden();
    $this->actingAs($admin)->patch(route('purchases.orders.status.update', $order), ['action' => 'confirm'])->assertRedirect();
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Confirmed)->and($order->fresh()->approved_by)->toBe($admin->id);
});

test('requires a reason and permission to cancel eligible orders', function () {
    $receptionist = User::factory()->create();
    $receptionist->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.crear']);
    $admin = User::factory()->create();
    $admin->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.cancelar']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receptionist, 'status' => PurchaseOrderStatus::Sent]);

    $this->actingAs($receptionist)->patch(route('purchases.orders.status.update', $order), ['action' => 'cancel', 'reason' => 'Sin stock'])->assertForbidden();
    $this->actingAs($admin)->patch(route('purchases.orders.status.update', $order), ['action' => 'cancel'])->assertSessionHasErrors('reason');
    $this->actingAs($admin)->patch(route('purchases.orders.status.update', $order), ['action' => 'cancel', 'reason' => 'Proveedor sin stock'])->assertRedirect();
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Cancelled)->and($order->fresh()->cancellation_reason)->toBe('Proveedor sin stock');
});

test('blocks invalid order transitions', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.crear', 'pedidos-compra.aprobar', 'pedidos-compra.cancelar']);
    $order = PurchaseOrder::factory()->create(['created_by' => $admin, 'status' => PurchaseOrderStatus::Confirmed]);

    $this->actingAs($admin)->patch(route('purchases.orders.status.update', $order), ['action' => 'send'])->assertForbidden();
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Confirmed);
});

test('prevents administrators from editing a received order despite the global gate', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');
    $order = PurchaseOrder::factory()->create(['created_by' => $admin, 'status' => PurchaseOrderStatus::Received]);
    $product = Product::factory()->create();
    $order->supplier->products()->attach($product);
    PurchaseOrderItem::factory()->create(['purchase_order_id' => $order, 'product_id' => $product]);

    $this->actingAs($admin)->patch(route('purchases.orders.update', $order), ['supplier_id' => $order->supplier_id, 'tax_rate' => '18', 'items' => [['product_id' => $product->id, 'quantity_ordered' => '1', 'unit_cost' => '10']]])->assertSessionHasErrors('order');
    $this->actingAs($admin)->delete(route('purchases.orders.destroy', $order))->assertForbidden();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
});
