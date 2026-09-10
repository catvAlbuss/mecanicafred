<?php

use App\Enums\MeasurementUnit;
use App\Enums\ProductType;
use App\Enums\SupplierAvailabilityStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use Database\Seeders\InventoryDemoSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;

test('casts catalog values and connects products with their category', function () {
    $category = ProductCategory::factory()->create(['type' => ProductType::SparePart]);
    $product = Product::factory()->for($category, 'category')->create([
        'unit' => MeasurementUnit::Liter,
        'minimum_stock' => 2.5,
        'current_stock' => 8.75,
    ]);

    expect($category->type)->toBe(ProductType::SparePart)
        ->and($product->unit)->toBe(MeasurementUnit::Liter)
        ->and($product->category->is($category))->toBeTrue()
        ->and($category->products->first()->is($product))->toBeTrue()
        ->and($product->minimum_stock)->toBe('2.500')
        ->and($product->current_stock)->toBe('8.750');
});

test('connects a supplier catalog with purchasing details', function () {
    $product = Product::factory()->create();
    $supplier = Supplier::factory()->create();

    $supplier->products()->attach($product, [
        'supplier_sku' => 'SUP-100',
        'last_unit_cost' => 24.5,
        'lead_time_days' => 4,
        'availability_status' => SupplierAvailabilityStatus::Available->value,
        'available_quantity' => 30,
        'is_preferred' => true,
    ]);

    $offeredProduct = $supplier->products()->firstOrFail();

    expect($offeredProduct->is($product))->toBeTrue()
        ->and($offeredProduct->pivot->supplier_sku)->toBe('SUP-100')
        ->and($offeredProduct->pivot->availability_status)->toBe('available')
        ->and($product->suppliers()->firstOrFail()->is($supplier))->toBeTrue();
});

test('seeds the inventory demonstration data idempotently', function () {
    $this->seed(InventoryDemoSeeder::class);
    $this->seed(InventoryDemoSeeder::class);

    expect(ProductCategory::query()->count())->toBe(4)
        ->and(Product::query()->count())->toBe(20)
        ->and(Supplier::query()->count())->toBe(2)
        ->and(Product::query()->whereNull('barcode')->count())->toBe(0)
        ->and(Product::query()->distinct()->count('barcode'))->toBe(20);

    ProductCategory::query()->each(function (ProductCategory $category) {
        expect($category->products()->count())->toBe(5);
    });
});

test('generates a unique scannable barcode for every new product', function () {
    $product = Product::factory()->create(['barcode' => null]);

    expect($product->fresh()->barcode)
        ->toBe(Product::generateBarcode($product->id))
        ->toMatch('/^\d{13}$/');
});

test('stores catalog images and replaces a supplier logo', function () {
    Storage::fake('public');
    $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
    $product = Product::factory()->create();
    $supplier = Supplier::factory()->create();

    $product->addMedia(UploadedFile::fake()->createWithContent('product.png', $imageContent))
        ->toMediaCollection('images');
    $supplier->addMedia(UploadedFile::fake()->createWithContent('first-logo.png', $imageContent))
        ->toMediaCollection('logo');
    $supplier->addMedia(UploadedFile::fake()->createWithContent('latest-logo.png', $imageContent))
        ->toMediaCollection('logo');

    expect($product->getMedia('images'))->toHaveCount(1)
        ->and($supplier->fresh()->getMedia('logo'))->toHaveCount(1)
        ->first()->file_name->toBe('latest-logo.png');
});

test('rejects unsupported product media', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    expect(fn () => $product
        ->addMedia(UploadedFile::fake()->create('notes.txt', 1, 'text/plain'))
        ->toMediaCollection('images'))
        ->toThrow(FileUnacceptableForCollection::class);
});
