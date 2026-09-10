<?php

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);
});

function inquiryManager(): User
{
    $user = User::factory()->create();
    $user->assignRole('Recepción');

    return $user;
}
function inquiryPayload(Supplier $supplier, Product $product, array $overrides = []): array
{
    return ['supplier_id' => $supplier->id, 'valid_until' => now()->addWeek()->toDateString(), 'notes' => 'Solicitar precio y disponibilidad.', 'items' => [['product_id' => $product->id, 'quantity_requested' => '10.500']], ...$overrides];
}

test('creates and updates a draft inquiry with catalog products and attachments', function () {
    Storage::fake('public');
    $manager = inquiryManager();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create();
    $supplier->products()->attach($product);
    $response = $this->actingAs($manager)->post(route('purchases.inquiries.store'), inquiryPayload($supplier, $product, ['attachments' => [UploadedFile::fake()->createWithContent('cotizacion.pdf', "%PDF-1.4\n%%EOF")]]));
    $inquiry = SupplierInquiry::query()->firstOrFail();
    $response->assertRedirect(route('purchases.inquiries.show', $inquiry));
    expect($inquiry->number)->toMatch('/^COT-\d{4}-\d{6}$/')->and($inquiry->items)->toHaveCount(1)->and($inquiry->getMedia('attachments'))->toHaveCount(1);
    $this->actingAs($manager)->patch(route('purchases.inquiries.update', $inquiry), inquiryPayload($supplier, $product, ['notes' => 'Actualizada']))->assertRedirect();
    expect($inquiry->fresh()->notes)->toBe('Actualizada');
});

test('lists and filters inquiries', function () {
    $manager = inquiryManager();
    $match = SupplierInquiry::factory()->sent()->create();
    SupplierInquiry::factory()->create();
    $this->actingAs($manager)->get(route('purchases.inquiries.index', ['status' => 'sent']))->assertOk()->assertInertia(fn (Assert $page) => $page->component('purchases/inquiries/Index')->has('inquiries.data', 1)->where('inquiries.data.0.id', $match->id));
});

test('rejects products outside the selected supplier catalog and unsupported files', function () {
    Storage::fake('public');
    $manager = inquiryManager();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create();
    $this->actingAs($manager)->post(route('purchases.inquiries.store'), inquiryPayload($supplier, $product, ['attachments' => [UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream')]]))->assertSessionHasErrors(['items.0.product_id', 'attachments.0']);
    expect(SupplierInquiry::query()->count())->toBe(0);
});

test('only draft inquiries can be edited or deleted', function () {
    $manager = inquiryManager();
    $sent = SupplierInquiry::factory()->sent()->create();
    $this->actingAs($manager)->get(route('purchases.inquiries.edit', $sent))->assertForbidden();
    $this->actingAs($manager)->delete(route('purchases.inquiries.destroy', $sent))->assertForbidden();
});

test('forbids mechanics from viewing inquiries', function () {
    $mechanic = User::factory()->create();
    $mechanic->assignRole('Mecánico');
    $this->actingAs($mechanic)->get(route('purchases.inquiries.index'))->assertForbidden();
});

test('deletes only attachments owned by the editable inquiry', function () {
    Storage::fake('public');
    $manager = inquiryManager();
    $inquiry = SupplierInquiry::factory()->create(['requested_by' => $manager]);
    $other = SupplierInquiry::factory()->create(['requested_by' => $manager]);
    $media = $inquiry->addMedia(UploadedFile::fake()->createWithContent('quote.pdf', "%PDF-1.4\n%%EOF"))->toMediaCollection('attachments');

    $this->actingAs($manager)->delete(route('purchases.inquiries.media.destroy', [$other, $media]))->assertNotFound();
    $this->assertModelExists($media);
    $this->actingAs($manager)->delete(route('purchases.inquiries.media.destroy', [$inquiry, $media]))->assertRedirect();
    $this->assertModelMissing($media);
});
