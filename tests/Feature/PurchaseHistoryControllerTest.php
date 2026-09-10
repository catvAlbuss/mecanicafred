<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);
});

test('shows received and cancelled orders with aggregate purchase totals', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('historial-compras.ver');
    $supplier = Supplier::factory()->create(['business_name' => 'Proveedor HistÃ³rico', 'trade_name' => null]);
    $received = PurchaseOrder::factory()->create(['supplier_id' => $supplier, 'created_by' => $user, 'status' => PurchaseOrderStatus::Received, 'total' => '118.00']);
    PurchaseOrderItem::factory()->create(['purchase_order_id' => $received, 'quantity_received' => '5.000']);
    PurchaseOrder::factory()->create(['supplier_id' => $supplier, 'created_by' => $user, 'status' => PurchaseOrderStatus::Cancelled, 'total' => '20.00']);
    PurchaseOrder::factory()->create(['supplier_id' => $supplier, 'created_by' => $user, 'status' => PurchaseOrderStatus::Confirmed]);

    $this->actingAs($user)->get(route('purchases.history.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('purchases/history/Index')->has('orders.data', 2)->where('stats.orders', 2)->where('stats.amount', '118')->where('stats.suppliers', 1)->where('stats.units', '5')->where('supplierSummary.0.supplier_name', 'Proveedor HistÃ³rico')->where('supplierSummary.0.orders_count', 1)->where('supplierSummary.0.total_amount', '118'));
});

test('filters purchase history by product and status', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('historial-compras.ver');
    $product = Product::factory()->create();
    $matching = PurchaseOrder::factory()->create(['created_by' => $user, 'status' => PurchaseOrderStatus::Received]);
    PurchaseOrderItem::factory()->create(['purchase_order_id' => $matching, 'product_id' => $product]);
    PurchaseOrder::factory()->create(['created_by' => $user, 'status' => PurchaseOrderStatus::Cancelled]);

    $this->actingAs($user)->get(route('purchases.history.index', ['product' => $product->id, 'status' => 'received']))->assertInertia(fn (Assert $page) => $page->has('orders.data', 1)->where('orders.data.0.id', $matching->id));
});

test('forbids purchase history without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('purchases.history.index'))->assertForbidden();
});
