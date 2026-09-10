<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);
});

test('creates a numbered draft and recalculates all totals on the server', function () {
    Storage::fake('public');
    $manager = User::factory()->create();
    $manager->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.crear']);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['name' => 'Filtro de aceite', 'sku' => 'FIL-100', 'current_stock' => '4.000']);
    $supplier->products()->attach($product, ['last_unit_cost' => '10.0000']);

    $response = $this->actingAs($manager)->post(route('purchases.orders.store'), [
        'supplier_id' => $supplier->id,
        'expected_at' => now()->addWeek()->toDateString(),
        'tax_rate' => '18',
        'subtotal' => '0.01',
        'tax' => '0.01',
        'total' => '0.02',
        'status' => 'confirmed',
        'items' => [['product_id' => $product->id, 'quantity_ordered' => '3.333', 'unit_cost' => '12.3456']],
        'attachments' => [UploadedFile::fake()->createWithContent('orden.pdf', "%PDF-1.4\n%%EOF")],
    ]);

    $order = PurchaseOrder::query()->firstOrFail();
    $response->assertRedirect(route('purchases.orders.show', $order));
    expect($order->number)->toMatch('/^OC-\d{4}-\d{6}$/')
        ->and($order->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($order->subtotal)->toBe('41.15')
        ->and($order->tax)->toBe('7.41')
        ->and($order->total)->toBe('48.56')
        ->and($order->items->first()->product_name)->toBe('Filtro de aceite')
        ->and($order->getMedia('attachments'))->toHaveCount(1)
        ->and($product->fresh()->current_stock)->toBe('4.000')
        ->and($product->inventoryMovements()->count())->toBe(0);
});

test('updates only a draft and refreshes product snapshots and totals', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.crear']);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['name' => 'BujÃ­a NGK', 'sku' => 'BUJ-10']);
    $supplier->products()->attach($product);
    $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier, 'created_by' => $manager]);

    $this->actingAs($manager)->patch(route('purchases.orders.update', $order), ['supplier_id' => $supplier->id, 'tax_rate' => '10', 'items' => [['product_id' => $product->id, 'quantity_ordered' => '2', 'unit_cost' => '50']]])->assertRedirect(route('purchases.orders.show', $order));

    expect($order->fresh()->subtotal)->toBe('100.00')->and($order->fresh()->tax)->toBe('10.00')->and($order->fresh()->total)->toBe('110.00')->and($order->items()->first()->product_sku)->toBe('BUJ-10');
});

test('rejects products outside the supplier catalog and invalid attachments', function () {
    Storage::fake('public');
    $manager = User::factory()->create();
    $manager->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.crear']);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($manager)->post(route('purchases.orders.store'), ['supplier_id' => $supplier->id, 'tax_rate' => '18', 'items' => [['product_id' => $product->id, 'quantity_ordered' => '1', 'unit_cost' => '10']], 'attachments' => [UploadedFile::fake()->create('malware.exe', 2)]])->assertSessionHasErrors(['items.0.product_id', 'attachments.0']);
    expect(PurchaseOrder::query()->count())->toBe(0);
});

test('lists active orders and allows explicit status filtering', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.crear']);
    $active = PurchaseOrder::factory()->create(['created_by' => $manager, 'status' => PurchaseOrderStatus::Sent]);
    $cancelled = PurchaseOrder::factory()->create(['created_by' => $manager, 'status' => PurchaseOrderStatus::Cancelled]);

    $this->actingAs($manager)->get(route('purchases.orders.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('purchases/orders/Index')->has('orders.data', 1)->where('orders.data.0.id', $active->id));
    $this->actingAs($manager)->get(route('purchases.orders.index', ['status' => 'cancelled']))->assertInertia(fn (Assert $page) => $page->has('orders.data', 1)->where('orders.data.0.id', $cancelled->id));
});

test('blocks edits and deletes after a draft is sent', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.crear']);
    $order = PurchaseOrder::factory()->create(['created_by' => $manager, 'status' => PurchaseOrderStatus::Sent]);

    $this->actingAs($manager)->get(route('purchases.orders.edit', $order))->assertForbidden();
    $this->actingAs($manager)->delete(route('purchases.orders.destroy', $order))->assertForbidden();
});

test('forbids mechanics from viewing purchase orders', function () {
    $mechanic = User::factory()->create();

    $this->actingAs($mechanic)->get(route('purchases.orders.index'))->assertForbidden();
});
