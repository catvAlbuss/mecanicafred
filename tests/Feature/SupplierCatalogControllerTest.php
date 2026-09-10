<?php

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);
});

test('manages products in a supplier catalog', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create(['is_active' => true]);
    $payload = ['product_id' => $product->id, 'supplier_sku' => 'PROV-01', 'last_unit_cost' => '25.1250', 'lead_time_days' => 3, 'availability_status' => 'available', 'available_quantity' => '12.500', 'is_preferred' => true];

    $this->actingAs($manager)->post(route('suppliers.catalog.store', $supplier), $payload)->assertRedirect();
    $this->assertDatabaseHas('product_supplier', ['supplier_id' => $supplier->id, 'product_id' => $product->id, 'availability_status' => 'available', 'available_quantity' => 12.500]);
    $this->actingAs($manager)->get(route('suppliers.catalog.index', $supplier))->assertOk()->assertInertia(fn (Assert $page) => $page->component('suppliers/Catalog')->has('catalog', 1)->where('catalog.0.supplier_sku', 'PROV-01'));
    $this->actingAs($manager)->patch(route('suppliers.catalog.update', [$supplier, $product]), [...$payload, 'availability_status' => 'limited', 'available_quantity' => '4.000'])->assertRedirect();
    $this->assertDatabaseHas('product_supplier', ['supplier_id' => $supplier->id, 'product_id' => $product->id, 'availability_status' => 'limited', 'available_quantity' => 4.000]);
    $this->actingAs($manager)->delete(route('suppliers.catalog.destroy', [$supplier, $product]))->assertRedirect();
    $this->assertDatabaseMissing('product_supplier', ['supplier_id' => $supplier->id, 'product_id' => $product->id]);
});

test('rejects duplicate catalog products and products owned by another catalog', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $supplier = Supplier::factory()->create();
    $other = Supplier::factory()->create();
    $product = Product::factory()->create();
    $supplier->products()->attach($product);
    $payload = ['product_id' => $product->id, 'availability_status' => 'unknown', 'is_preferred' => false];
    $this->actingAs($manager)->post(route('suppliers.catalog.store', $supplier), $payload)->assertSessionHasErrors('product_id');
    $this->actingAs($manager)->patch(route('suppliers.catalog.update', [$other, $product]), $payload)->assertNotFound();
});

test('forbids mechanics from managing supplier catalogs', function () {
    $mechanic = User::factory()->create();
    $mechanic->assignRole('Mecánico');
    $supplier = Supplier::factory()->create();
    $this->actingAs($mechanic)->get(route('suppliers.catalog.index', $supplier))->assertForbidden();
});
