<?php

use App\Models\Product;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('downloads an Excel compatible file with only code product and quantity', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('consultas-proveedor.ver');
    $inquiry = SupplierInquiry::factory()->create();
    $product = Product::factory()->create([
        'sku' => '+ACEITE-001',
        'name' => ' =Aceite premium',
    ]);
    SupplierInquiryItem::factory()->create([
        'supplier_inquiry_id' => $inquiry,
        'product_id' => $product,
        'quantity_requested' => '2.500',
    ]);

    $response = $this->actingAs($user)->get(route('purchases.inquiries.export', $inquiry));

    $response
        ->assertDownload('cotizacion-'.strtolower($inquiry->number).'.csv')
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $lines = preg_split('/\r\n|\r|\n/', trim($response->streamedContent()));
    $header = str_getcsv(ltrim($lines[0], "\xEF\xBB\xBF"), ';');
    $row = str_getcsv($lines[1], ';');

    expect($header)->toBe(['Código', 'Producto', 'Cantidad'])
        ->and($row)->toBe(["'+ACEITE-001", "' =Aceite premium", '2,5']);
});

test('redirects guests and forbids users without inquiry access', function () {
    $inquiry = SupplierInquiry::factory()->create();

    $this->get(route('purchases.inquiries.export', $inquiry))
        ->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('purchases.inquiries.export', $inquiry))
        ->assertForbidden();
});
