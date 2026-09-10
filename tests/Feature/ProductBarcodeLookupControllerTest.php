<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function inventoryViewer(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('inventario.ver');

    return $user;
}

test('resolves a product by its barcode', function () {
    $product = Product::factory()->create(['last_purchase_cost' => '12.5000']);

    $this->actingAs(inventoryViewer())
        ->getJson(route('inventory.products.barcode.lookup', ['code' => $product->fresh()->barcode]))
        ->assertOk()
        ->assertJson([
            'id' => $product->id,
            'sku' => $product->sku,
            'last_purchase_cost' => '12.5000',
        ]);
});

test('also resolves a product by its sku', function () {
    $product = Product::factory()->create(['sku' => 'REP-999']);

    $this->actingAs(inventoryViewer())
        ->getJson(route('inventory.products.barcode.lookup', ['code' => 'REP-999']))
        ->assertOk()
        ->assertJsonPath('id', $product->id);
});

test('returns 404 when no product matches the code', function () {
    $this->actingAs(inventoryViewer())
        ->getJson(route('inventory.products.barcode.lookup', ['code' => '0000000000000']))
        ->assertNotFound();
});

test('forbids the lookup without inventory permission', function () {
    Product::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('inventory.products.barcode.lookup', ['code' => '123']))
        ->assertForbidden();
});
