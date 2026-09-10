<?php

use App\Models\PurchaseOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
});

test('deletes only attachments belonging to the editable order', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(['pedidos-compra.ver', 'pedidos-compra.crear']);
    $order = PurchaseOrder::factory()->create(['created_by' => $manager]);
    $other = PurchaseOrder::factory()->create(['created_by' => $manager]);
    $media = $order->addMedia(UploadedFile::fake()->createWithContent('order.pdf', "%PDF-1.4\n%%EOF"))->toMediaCollection('attachments');

    $this->actingAs($manager)->delete(route('purchases.orders.media.destroy', [$other, $media]))->assertNotFound();
    $this->assertModelExists($media);
    $this->actingAs($manager)->delete(route('purchases.orders.media.destroy', [$order, $media]))->assertRedirect();
    $this->assertModelMissing($media);
});
