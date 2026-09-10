<?php

use App\Enums\CashTransactionType;
use App\Enums\InventoryMovementType;
use App\Enums\SaleStatus;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function sellerWithVoid(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(['ventas.ver', 'ventas.registrar', 'ventas.anular']);

    return $user;
}

test('cancelling a sale returns stock and books a refund', function () {
    $user = sellerWithVoid();
    CashRegister::factory()->create(['opened_by' => $user]);
    $product = Product::factory()->create(['current_stock' => '10.000']);

    $this->actingAs($user)->post(route('sales.store'), [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => '3.000', 'unit_price' => '20.00']],
    ]);
    $sale = Sale::query()->firstOrFail();
    expect($product->fresh()->current_stock)->toBe('7.000');

    $this->actingAs($user)->post(route('sales.cancel', $sale), ['reason' => 'Cliente se arrepintió'])
        ->assertRedirect(route('sales.show', $sale));

    $sale->refresh();
    expect($sale->status)->toBe(SaleStatus::Cancelled)
        ->and($sale->cancellation_reason)->toBe('Cliente se arrepintió')
        ->and($product->fresh()->current_stock)->toBe('10.000');

    $return = InventoryMovement::query()->where('type', InventoryMovementType::SaleReturn)->firstOrFail();
    expect($return->quantity)->toBe('3.000')
        ->and($return->stock_after)->toBe('10.000');

    $refund = CashTransaction::query()->where('type', CashTransactionType::Expense)->firstOrFail();
    expect((float) $refund->amount)->toBe(60.0)
        ->and($refund->category->value)->toBe('product_sale');
});

test('a sale cannot be cancelled twice', function () {
    $user = sellerWithVoid();
    CashRegister::factory()->create(['opened_by' => $user]);
    $product = Product::factory()->create(['current_stock' => '10.000']);
    $this->actingAs($user)->post(route('sales.store'), [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => '1.000', 'unit_price' => '10.00']],
    ]);
    $sale = Sale::query()->firstOrFail();

    $this->actingAs($user)->post(route('sales.cancel', $sale), ['reason' => 'Primer intento'])->assertRedirect();
    $this->actingAs($user)->post(route('sales.cancel', $sale), ['reason' => 'Segundo intento'])->assertForbidden();

    expect(InventoryMovement::query()->where('type', InventoryMovementType::SaleReturn)->count())->toBe(1);
});

test('forbids cancelling without the void permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['ventas.ver', 'ventas.registrar']);
    CashRegister::factory()->create(['opened_by' => $user]);
    $product = Product::factory()->create(['current_stock' => '10.000']);
    $this->actingAs($user)->post(route('sales.store'), [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => '1.000', 'unit_price' => '10.00']],
    ]);
    $sale = Sale::query()->firstOrFail();

    $this->actingAs($user)->post(route('sales.cancel', $sale), ['reason' => 'Sin permiso'])->assertForbidden();
});
