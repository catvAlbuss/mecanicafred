<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierInquiryStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('sends and answers an inquiry without changing inventory stock', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $product = Product::factory()->create(['current_stock' => '7.250']);
    $supplier = Supplier::factory()->create();
    $supplier->products()->attach($product);
    $inquiry = SupplierInquiry::factory()->create(['supplier_id' => $supplier, 'requested_by' => $manager]);
    $item = SupplierInquiryItem::factory()->create(['supplier_inquiry_id' => $inquiry, 'product_id' => $product, 'quantity_requested' => '10.000']);
    $this->actingAs($manager)->patch(route('purchases.inquiries.status.update', $inquiry), ['action' => 'send'])->assertRedirect();
    expect($inquiry->fresh()->status)->toBe(SupplierInquiryStatus::Sent)->and($inquiry->fresh()->requested_at)->not->toBeNull();
    $this->actingAs($manager)->put(route('purchases.inquiries.response.update', $inquiry), ['valid_until' => now()->addWeek()->toDateString(), 'items' => [['id' => $item->id, 'is_available' => true, 'quantity_available' => '8.000', 'quoted_unit_cost' => '12.3456', 'supplier_notes' => 'Entrega en tres días']]])->assertRedirect();
    expect($inquiry->fresh()->status)->toBe(SupplierInquiryStatus::Answered)->and($item->fresh()->quoted_unit_cost)->toBe('12.3456')->and($product->fresh()->current_stock)->toBe('7.250')->and($product->inventoryMovements()->count())->toBe(0);
});

test('blocks invalid inquiry transitions and incomplete responses', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $inquiry = SupplierInquiry::factory()->create(['requested_by' => $manager]);
    SupplierInquiryItem::factory()->create(['supplier_inquiry_id' => $inquiry]);
    $this->actingAs($manager)->patch(route('purchases.inquiries.status.update', $inquiry), ['action' => 'close'])->assertSessionHasErrors('action');
    $this->actingAs($manager)->put(route('purchases.inquiries.response.update', $inquiry), ['items' => []])->assertSessionHasErrors('items');
    expect($inquiry->fresh()->status)->toBe(SupplierInquiryStatus::Draft);
});

test('converts an answered inquiry into one exact idempotent draft order without changing stock', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $product = Product::factory()->create(['current_stock' => '4.000', 'name' => 'Filtro premium', 'sku' => 'FIL-99']);
    $inquiry = SupplierInquiry::factory()->answered()->create(['requested_by' => $manager]);
    SupplierInquiryItem::factory()->available()->create(['supplier_inquiry_id' => $inquiry, 'product_id' => $product, 'quantity_requested' => '10.000', 'quantity_available' => '8.000', 'quoted_unit_cost' => '12.3456']);
    $this->actingAs($manager)->post(route('purchases.inquiries.convert.store', $inquiry))->assertRedirect();
    $this->actingAs($manager)->post(route('purchases.inquiries.convert.store', $inquiry))->assertRedirect();
    $order = PurchaseOrder::query()->firstOrFail();
    expect(PurchaseOrder::query()->count())->toBe(1)->and($order->status)->toBe(PurchaseOrderStatus::Draft)->and($order->subtotal)->toBe('98.76')->and($order->items)->toHaveCount(1)->and($order->items->first()->quantity_ordered)->toBe('8.000')->and($product->fresh()->current_stock)->toBe('4.000')->and($product->inventoryMovements()->count())->toBe(0);
});

test('does not convert inquiries without an available quoted item', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $inquiry = SupplierInquiry::factory()->answered()->create(['requested_by' => $manager]);
    SupplierInquiryItem::factory()->create(['supplier_inquiry_id' => $inquiry, 'is_available' => false]);
    $this->actingAs($manager)->post(route('purchases.inquiries.convert.store', $inquiry))->assertSessionHasErrors('inquiry');
    expect(PurchaseOrder::query()->count())->toBe(0);
});

test('does not convert an inquiry before the supplier answers it', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $inquiry = SupplierInquiry::factory()->sent()->create(['requested_by' => $manager]);

    $this->actingAs($manager)->post(route('purchases.inquiries.convert.store', $inquiry))->assertSessionHasErrors('inquiry');

    expect(PurchaseOrder::query()->count())->toBe(0);
});
