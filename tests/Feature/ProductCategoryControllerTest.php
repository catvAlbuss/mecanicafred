<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);
});

test('lists and manages product categories', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $this->actingAs($manager)->post(route('inventory.categories.store'), ['name' => 'Filtros especiales', 'type' => 'spare_part', 'description' => 'Filtros del motor', 'is_active' => true])->assertRedirect(route('inventory.categories.index'));
    $category = ProductCategory::query()->where('slug', 'filtros-especiales')->firstOrFail();
    $this->actingAs($manager)->get(route('inventory.categories.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('inventory/Categories')->has('categories', 1)->where('categories.0.products_count', 0)->where('canManage', true));
    $this->actingAs($manager)->patch(route('inventory.categories.update', $category), ['name' => 'Filtros premium', 'type' => 'spare_part', 'description' => null, 'is_active' => true])->assertRedirect();
    expect($category->fresh()->slug)->toBe('filtros-premium');
});

test('deactivates used categories and deletes unused categories', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $used = ProductCategory::factory()->create();
    Product::factory()->create(['product_category_id' => $used]);
    $unused = ProductCategory::factory()->create();
    $this->actingAs($manager)->delete(route('inventory.categories.destroy', $used))->assertRedirect();
    $this->actingAs($manager)->delete(route('inventory.categories.destroy', $unused))->assertRedirect();
    expect($used->fresh()->is_active)->toBeFalse();
    $this->assertModelMissing($unused);
});

test('forbids mechanics from category administration', function () {
    $mechanic = User::factory()->create();
    $mechanic->assignRole('Mecánico');
    $this->actingAs($mechanic)->get(route('inventory.categories.index'))->assertForbidden();
    $this->actingAs($mechanic)->post(route('inventory.categories.store'), ['name' => 'Intento', 'type' => 'tool', 'is_active' => true])->assertForbidden();
});
