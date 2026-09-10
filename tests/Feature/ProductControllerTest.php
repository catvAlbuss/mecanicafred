<?php

use App\Enums\InventoryMovementType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);
});

function inventoryManager(): User
{
    $user = User::factory()->create();
    $user->assignRole('Recepción');

    return $user;
}
function validProductPayload(ProductCategory $category, array $overrides = []): array
{
    return ['product_category_id' => $category->id, 'sku' => 'FRE-001', 'name' => 'Filtro de aceite', 'description' => 'Filtro para motor.', 'brand' => 'Racing', 'unit' => 'unit', 'minimum_stock' => '3.000', 'initial_stock' => '8.500', 'location' => 'A-01', 'last_purchase_cost' => '18.7500', 'is_active' => true, ...$overrides];
}

test('requires authentication and inventory permission', function () {
    $this->get(route('inventory.products.index'))->assertRedirect(route('login'));
    $mechanic = User::factory()->create();
    $mechanic->assignRole('Mecánico');
    $this->actingAs($mechanic)->get(route('inventory.products.index'))->assertOk();
    $this->actingAs($mechanic)->post(route('inventory.products.store'), [])->assertForbidden();
});

test('lists products and filters stock', function () {
    $manager = inventoryManager();
    Product::factory()->count(12)->create();
    $out = Product::factory()->outOfStock()->create(['name' => 'Producto agotado']);
    $this->actingAs($manager)->get(route('inventory.products.index', ['stock' => 'out']))->assertOk()->assertInertia(fn (Assert $page) => $page->component('inventory/Index')->has('products.data', 1)->where('products.data.0.id', $out->id)->where('stats.total', 13));
});

test('finds a product by its scannable barcode', function () {
    $manager = inventoryManager();
    Product::factory()->count(3)->create();
    $target = Product::factory()->create(['name' => 'Aceite sintético']);

    $this->actingAs($manager)
        ->get(route('inventory.products.index', ['search' => $target->fresh()->barcode]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inventory/Index')
            ->has('products.data', 1)
            ->where('products.data.0.id', $target->id));
});

test('creates a product with an exact opening movement and image', function () {
    Storage::fake('public');
    $manager = inventoryManager();
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $response = $this->actingAs($manager)->post(route('inventory.products.store'), validProductPayload($category, ['images' => [UploadedFile::fake()->image('filtro.webp')]]));
    $product = Product::query()->where('sku', 'FRE-001')->firstOrFail();
    $response->assertRedirect(route('inventory.products.show', $product));
    expect($product->current_stock)->toBe('8.500')->and($product->getMedia('images'))->toHaveCount(1)->and($product->getFirstMedia('images')->getCustomProperty('is_primary'))->toBeTrue();
    $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'type' => InventoryMovementType::OpeningBalance->value, 'quantity' => 8.500, 'stock_before' => 0, 'stock_after' => 8.500]);
});

test('rejects invalid product images', function () {
    Storage::fake('public');
    $manager = inventoryManager();
    $category = ProductCategory::factory()->create(['is_active' => true]);

    $this->actingAs($manager)->post(route('inventory.products.store'), validProductPayload($category, [
        'images' => [UploadedFile::fake()->create('script.php', 10, 'application/x-php')],
    ]))->assertSessionHasErrors('images.0');

    expect(Product::query()->where('sku', 'FRE-001')->exists())->toBeFalse();
});

test('validates product fields and does not accept current stock on update', function () {
    $manager = inventoryManager();
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['product_category_id' => $category]);
    $this->actingAs($manager)->post(route('inventory.products.store'), validProductPayload($category, ['sku' => $product->sku, 'initial_stock' => '-1']))->assertSessionHasErrors(['sku', 'initial_stock']);
    $payload = validProductPayload($category, ['sku' => $product->sku, 'name' => 'Nombre actualizado', 'current_stock' => 999]);
    unset($payload['initial_stock']);
    $this->actingAs($manager)->patch(route('inventory.products.update', $product), $payload)->assertSessionHasErrors('current_stock');
    expect($product->fresh()->current_stock)->toBe($product->current_stock);
});

test('deactivates a product with movement history instead of deleting it', function () {
    $manager = inventoryManager();
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $this->actingAs($manager)->post(route('inventory.products.store'), validProductPayload($category));
    $product = Product::query()->where('sku', 'FRE-001')->firstOrFail();
    $this->actingAs($manager)->delete(route('inventory.products.destroy', $product))->assertRedirect(route('inventory.products.index'));
    expect($product->fresh()->is_active)->toBeFalse();
});

test('deactivates a product with inquiry history after it leaves the supplier catalog', function () {
    $manager = inventoryManager();
    $product = Product::factory()->create(['is_active' => true]);
    $inquiry = SupplierInquiry::factory()->create(['requested_by' => $manager]);
    SupplierInquiryItem::factory()->create(['supplier_inquiry_id' => $inquiry, 'product_id' => $product]);

    $this->actingAs($manager)->delete(route('inventory.products.destroy', $product))->assertRedirect();

    expect($product->fresh()->is_active)->toBeFalse();
});
