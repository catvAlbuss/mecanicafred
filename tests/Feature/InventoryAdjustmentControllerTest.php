<?php

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('registers exact stock entries and exits with an immutable audit trail', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $product = Product::factory()->create(['current_stock' => '10.250']);
    $this->actingAs($manager)->post(route('inventory.products.adjustments.store', $product), ['direction' => 'in', 'quantity' => '2.125', 'reason' => 'Conteo físico'])->assertRedirect(route('inventory.products.show', $product));
    expect($product->fresh()->current_stock)->toBe('12.375');
    $this->actingAs($manager)->post(route('inventory.products.adjustments.store', $product), ['direction' => 'out', 'quantity' => '1.100', 'reason' => 'Merma comprobada'])->assertRedirect();
    expect($product->fresh()->current_stock)->toBe('11.275');
    $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'type' => InventoryMovementType::AdjustmentOut->value, 'quantity' => -1.100, 'stock_before' => 12.375, 'stock_after' => 11.275]);
});

test('rejects an exit over available stock without changing either table', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $product = Product::factory()->create(['current_stock' => '2.000']);
    $this->actingAs($manager)->post(route('inventory.products.adjustments.store', $product), ['direction' => 'out', 'quantity' => '2.001', 'reason' => 'Salida'])->assertSessionHasErrors(['quantity' => 'La salida supera el stock disponible.']);
    expect($product->fresh()->current_stock)->toBe('2.000')->and($product->inventoryMovements()->count())->toBe(0);
});

test('forbids mechanics from adjusting stock', function () {
    $mechanic = User::factory()->create();
    $mechanic->assignRole('Mecánico');
    $product = Product::factory()->create();
    $this->actingAs($mechanic)->post(route('inventory.products.adjustments.store', $product), ['direction' => 'in', 'quantity' => '1', 'reason' => 'Intento'])->assertForbidden();
});

test('prevents modifying or deleting inventory movements', function () {
    $movement = InventoryMovement::factory()->create();

    expect(fn () => $movement->update(['reason' => 'Alterado']))->toThrow(LogicException::class)
        ->and(fn () => $movement->delete())->toThrow(LogicException::class);
});
