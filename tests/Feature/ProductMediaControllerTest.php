<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
});

test('sets the primary image and replaces it after deletion', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $product = Product::factory()->create();
    $first = $product->addMedia(UploadedFile::fake()->image('first.jpg'))->withCustomProperties(['is_primary' => true])->toMediaCollection('images');
    $second = $product->addMedia(UploadedFile::fake()->image('second.jpg'))->withCustomProperties(['is_primary' => false])->toMediaCollection('images');
    $this->actingAs($manager)->patch(route('inventory.products.media.primary', [$product, $second]))->assertRedirect();
    expect($first->fresh()->getCustomProperty('is_primary'))->toBeFalse()->and($second->fresh()->getCustomProperty('is_primary'))->toBeTrue();
    $this->actingAs($manager)->delete(route('inventory.products.media.destroy', [$product, $second]))->assertRedirect();
    expect($first->fresh()->getCustomProperty('is_primary'))->toBeTrue();
});

test('does not allow changing media belonging to another product', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Recepción');
    $product = Product::factory()->create();
    $other = Product::factory()->create();
    $media = $other->addMedia(UploadedFile::fake()->image('other.jpg'))->toMediaCollection('images');
    $this->actingAs($manager)->delete(route('inventory.products.media.destroy', [$product, $media]))->assertNotFound();
    $this->assertModelExists($media);
});
