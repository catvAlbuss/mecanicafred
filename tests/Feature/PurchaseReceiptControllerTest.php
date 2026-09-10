<?php

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);
});

test('registers partial and final receipts while keeping stock and movements aligned', function () {
    $receiver = User::factory()->create();
    $receiver->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.recibir']);
    $product = Product::factory()->create(['current_stock' => '10.000']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Confirmed]);
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order, 'product_id' => $product, 'quantity_ordered' => '5.000', 'quantity_received' => '0', 'unit_cost' => '12.5000']);

    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), ['idempotency_key' => fake()->uuid(), 'received_at' => now()->toDateTimeString(), 'supplier_document_number' => 'G-001', 'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '2.000']]])->assertRedirect(route('purchases.orders.show', $order));

    $firstReceipt = PurchaseReceipt::query()->firstOrFail();
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived)
        ->and($item->fresh()->quantity_received)->toBe('2.000')
        ->and($product->fresh()->current_stock)->toBe('12.000')
        ->and($product->fresh()->last_purchase_cost)->toBe('12.5000')
        ->and($firstReceipt->number)->toMatch('/^REC-\d{4}-\d{6}$/');
    $movement = InventoryMovement::query()->firstOrFail();
    expect($movement->type)->toBe(InventoryMovementType::Receipt)->and($movement->reference->is($firstReceipt))->toBeTrue()->and($movement->stock_before)->toBe('10.000')->and($movement->stock_after)->toBe('12.000');
    $this->actingAs($receiver)->get(route('purchases.orders.show', $order))->assertInertia(fn (Assert $page) => $page->has('order.receipts', 1)->where('order.receipts.0.number', $firstReceipt->number)->where('order.receipts.0.items.0.product_name', $item->product_name));

    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), ['idempotency_key' => fake()->uuid(), 'received_at' => now()->toDateTimeString(), 'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '3.000']]])->assertRedirect();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received)->and($order->fresh()->received_at)->not->toBeNull()->and($item->fresh()->quantity_received)->toBe('5.000')->and($product->fresh()->current_stock)->toBe('15.000')->and(PurchaseReceipt::query()->count())->toBe(2)->and(InventoryMovement::query()->count())->toBe(2);
});

test('receives with an updated unit cost and propagates it to the product and supplier catalog', function () {
    $receiver = User::factory()->create();
    $receiver->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.recibir']);
    $product = Product::factory()->create(['current_stock' => '4.000', 'last_purchase_cost' => '10.0000']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Confirmed]);
    $order->supplier->products()->attach($product, ['availability_status' => 'available', 'last_unit_cost' => '10.0000', 'is_preferred' => true]);
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order, 'product_id' => $product, 'quantity_ordered' => '6.000', 'quantity_received' => '0', 'unit_cost' => '10.0000']);

    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), [
        'idempotency_key' => fake()->uuid(),
        'received_at' => now()->toDateTimeString(),
        'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '6.000', 'unit_cost' => '11.7500']],
    ])->assertRedirect(route('purchases.orders.show', $order));

    $receiptItem = PurchaseReceipt::query()->firstOrFail()->items()->firstOrFail();
    expect($receiptItem->unit_cost)->toBe('11.7500')
        ->and($product->fresh()->last_purchase_cost)->toBe('11.7500')
        ->and($product->fresh()->current_stock)->toBe('10.000')
        ->and((float) $product->suppliers()->firstOrFail()->pivot->last_unit_cost)->toBe(11.75);
});

test('keeps the ordered cost when the receipt does not override it', function () {
    $receiver = User::factory()->create();
    $receiver->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.recibir']);
    $product = Product::factory()->create(['current_stock' => '0.000']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Confirmed]);
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order, 'product_id' => $product, 'quantity_ordered' => '2.000', 'quantity_received' => '0', 'unit_cost' => '7.3000']);

    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), [
        'idempotency_key' => fake()->uuid(),
        'received_at' => now()->toDateTimeString(),
        'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '2.000']],
    ])->assertRedirect();

    expect(PurchaseReceipt::query()->firstOrFail()->items()->firstOrFail()->unit_cost)->toBe('7.3000')
        ->and($product->fresh()->last_purchase_cost)->toBe('7.3000');
});

test('rejects a non-positive unit cost', function () {
    $receiver = User::factory()->create();
    $receiver->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.recibir']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Confirmed]);
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order]);

    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), [
        'idempotency_key' => fake()->uuid(),
        'received_at' => now()->toDateTimeString(),
        'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '1.000', 'unit_cost' => '0']],
    ])->assertSessionHasErrors('items.0.unit_cost');

    expect(PurchaseReceipt::query()->count())->toBe(0);
});

test('does not duplicate a completed receipt when the same idempotency key is retried', function () {
    $receiver = User::factory()->create();
    $receiver->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.recibir']);
    $product = Product::factory()->create(['current_stock' => '1.000']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Confirmed]);
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order, 'product_id' => $product, 'quantity_ordered' => '2.000', 'quantity_received' => '0']);
    $key = fake()->uuid();
    $payload = ['idempotency_key' => $key, 'received_at' => now()->toDateTimeString(), 'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '2.000']]];

    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), $payload)->assertRedirect();
    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), $payload)->assertRedirect();

    expect(PurchaseReceipt::query()->count())->toBe(1)->and(InventoryMovement::query()->count())->toBe(1)->and($product->fresh()->current_stock)->toBe('3.000')->and($item->fresh()->quantity_received)->toBe('2.000');
});

test('rejects an excess and rolls back receipt stock and movement changes', function () {
    $receiver = User::factory()->create();
    $receiver->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.recibir']);
    $product = Product::factory()->create(['current_stock' => '8.000']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Confirmed]);
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order, 'product_id' => $product, 'quantity_ordered' => '4.000', 'quantity_received' => '3.000']);

    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), ['idempotency_key' => fake()->uuid(), 'received_at' => now()->toDateTimeString(), 'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '1.001']]])->assertSessionHasErrors('items');

    expect(PurchaseReceipt::query()->count())->toBe(0)->and(InventoryMovement::query()->count())->toBe(0)->and($product->fresh()->current_stock)->toBe('8.000')->and($item->fresh()->quantity_received)->toBe('3.000');
});

test('requires a positive line and valid documents', function () {
    Storage::fake('public');
    $receiver = User::factory()->create();
    $receiver->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.recibir']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Confirmed]);
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order]);

    $payload = ['idempotency_key' => fake()->uuid(), 'received_at' => now()->toDateTimeString(), 'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '0']]];
    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), $payload)->assertSessionHasErrors(['items' => 'Ingresa al menos una cantidad recibida mayor que cero.']);
    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), [...$payload, 'idempotency_key' => fake()->uuid(), 'attachments' => [UploadedFile::fake()->create('script.exe', 2)]])->assertSessionHasErrors(['attachments.0']);
    expect(PurchaseReceipt::query()->count())->toBe(0);
});

test('stores an accepted receipt attachment and keeps receipts immutable', function () {
    Storage::fake('public');
    $receiver = User::factory()->create();
    $receiver->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.recibir']);
    $order = PurchaseOrder::factory()->create(['created_by' => $receiver, 'status' => PurchaseOrderStatus::Confirmed]);
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order]);

    $this->actingAs($receiver)->post(route('purchases.orders.receipts.store', $order), ['idempotency_key' => fake()->uuid(), 'received_at' => now()->toDateTimeString(), 'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '1']], 'attachments' => [UploadedFile::fake()->createWithContent('guia.pdf', "%PDF-1.4\n%%EOF")]])->assertRedirect();

    $receipt = PurchaseReceipt::query()->firstOrFail();
    expect($receipt->getMedia('attachments'))->toHaveCount(1)->and(fn () => $receipt->update(['notes' => 'Cambio']))->toThrow(LogicException::class)->and(fn () => $receipt->items->first()->delete())->toThrow(LogicException::class);
});

test('forbids receiving without the purchase receipt permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('pedidos-compra.ver');
    $order = PurchaseOrder::factory()->create(['created_by' => $user, 'status' => PurchaseOrderStatus::Confirmed]);
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $order]);

    $this->actingAs($user)->post(route('purchases.orders.receipts.store', $order), ['idempotency_key' => fake()->uuid(), 'received_at' => now()->toDateTimeString(), 'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => '1']]])->assertForbidden();
});
