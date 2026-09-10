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

function createSupplierManager(): User
{
    $user = User::factory()->create();
    $user->assignRole('Recepción');

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validSupplierPayload(array $overrides = []): array
{
    return [
        'tax_id' => '20609988776',
        'business_name' => 'Repuestos del Norte S.A.C.',
        'trade_name' => 'Repuestos Norte',
        'contact_name' => 'Ana Torres',
        'phone' => '987654321',
        'secondary_phone' => '016543210',
        'email' => 'ventas@repuestos.test',
        'address' => 'Av. Industrial 150',
        'district' => 'Los Olivos',
        'province' => 'Lima',
        'notes' => 'Entrega programada por las mañanas.',
        'is_active' => true,
        ...$overrides,
    ];
}

test('redirects guests from the supplier list to login', function () {
    $this->get(route('suppliers.index'))
        ->assertRedirect(route('login'));
});

test('forbids mechanics from viewing suppliers', function () {
    $mechanic = User::factory()->create();
    $mechanic->assignRole('Mecánico');

    $this->actingAs($mechanic)
        ->get(route('suppliers.index'))
        ->assertForbidden();
});

test('renders a paginated supplier list with summary data', function () {
    $manager = createSupplierManager();
    Supplier::factory()->count(13)->create();

    $this->actingAs($manager)
        ->get(route('suppliers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('suppliers/Index')
            ->has('suppliers.data', 12)
            ->where('suppliers.current_page', 1)
            ->where('suppliers.last_page', 2)
            ->where('stats.total', 13)
            ->where('canManage', true));
});

test('searches suppliers by company tax id contact and offered product', function (string $search) {
    $manager = createSupplierManager();
    $matchingSupplier = Supplier::factory()->create([
        'tax_id' => '20111222333',
        'business_name' => 'Frenos Express S.A.C.',
        'contact_name' => 'Carlos Mendoza',
    ]);
    Supplier::factory()->create(['business_name' => 'Lubricantes del Sur S.A.C.']);
    $product = Product::factory()->create(['name' => 'Pastillas cerámicas', 'sku' => 'FRE-900']);
    $matchingSupplier->products()->attach($product);

    $this->actingAs($manager)
        ->get(route('suppliers.index', ['search' => $search]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.id', $matchingSupplier->id)
            ->where('filters.search', $search));
})->with([
    'company' => 'Frenos Express',
    'tax id' => '20111222333',
    'contact' => 'Carlos Mendoza',
    'product name' => 'Pastillas cerámicas',
    'product sku' => 'FRE-900',
]);

test('filters suppliers by active status', function () {
    $manager = createSupplierManager();
    $activeSupplier = Supplier::factory()->create(['is_active' => true]);
    Supplier::factory()->inactive()->create();

    $this->actingAs($manager)
        ->get(route('suppliers.index', ['status' => 'active']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.id', $activeSupplier->id));
});

test('creates a supplier and redirects to its detail', function () {
    $manager = createSupplierManager();

    $response = $this->actingAs($manager)
        ->post(route('suppliers.store'), validSupplierPayload(['id' => 999]));

    $supplier = Supplier::query()->where('tax_id', '20609988776')->firstOrFail();
    $response->assertRedirect(route('suppliers.show', $supplier));
    $this->assertModelExists($supplier);
    expect($supplier->business_name)->toBe('Repuestos del Norte S.A.C.')
        ->and($supplier->id)->not->toBe(999)
        ->and($supplier->is_active)->toBeTrue();
});

test('treats search control characters as data instead of query syntax', function () {
    $manager = createSupplierManager();
    Supplier::factory()->count(2)->create();

    $this->actingAs($manager)
        ->get(route('suppliers.index', ['search' => "%') OR 1=1 --"]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('suppliers.data', 0));
});

test('shows required field messages when creating a supplier', function () {
    $manager = createSupplierManager();

    $this->actingAs($manager)
        ->post(route('suppliers.store'), [])
        ->assertSessionHasErrors([
            'tax_id' => 'El RUC o documento fiscal es obligatorio.',
            'business_name' => 'La razón social es obligatoria.',
        ]);

    expect(Supplier::query()->count())->toBe(0);
});

test('rejects duplicate tax ids and invalid email addresses', function () {
    $manager = createSupplierManager();
    Supplier::factory()->create(['tax_id' => '20609988776']);

    $this->actingAs($manager)
        ->post(route('suppliers.store'), validSupplierPayload(['email' => 'correo-invalido']))
        ->assertSessionHasErrors([
            'tax_id' => 'Ya existe un proveedor con este RUC o documento fiscal.',
            'email' => 'Ingresa un correo electrónico válido.',
        ]);

    expect(Supplier::query()->count())->toBe(1);
});

test('forbids mechanics from creating suppliers', function () {
    $mechanic = User::factory()->create();
    $mechanic->assignRole('Mecánico');

    $this->actingAs($mechanic)
        ->post(route('suppliers.store'), validSupplierPayload())
        ->assertForbidden();

    expect(Supplier::query()->count())->toBe(0);
});

test('renders supplier detail and edit pages for managers', function () {
    $manager = createSupplierManager();
    $supplier = Supplier::factory()->create();

    $this->actingAs($manager)
        ->get(route('suppliers.show', $supplier))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('suppliers/Show')
            ->where('supplier.id', $supplier->id)
            ->where('canManage', true));

    $this->actingAs($manager)
        ->get(route('suppliers.edit', $supplier))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('suppliers/Edit')
            ->where('supplier.id', $supplier->id));
});

test('updates a supplier without treating its own tax id as duplicated', function () {
    $manager = createSupplierManager();
    $supplier = Supplier::factory()->create(['tax_id' => '20609988776']);

    $this->actingAs($manager)
        ->patch(route('suppliers.update', $supplier), validSupplierPayload([
            'business_name' => 'Nombre Comercial Actualizado S.A.C.',
        ]))
        ->assertRedirect(route('suppliers.show', $supplier));

    expect($supplier->fresh()->business_name)->toBe('Nombre Comercial Actualizado S.A.C.');
});

test('activates and deactivates a supplier', function () {
    $manager = createSupplierManager();
    $supplier = Supplier::factory()->create(['is_active' => true]);

    $this->actingAs($manager)
        ->patch(route('suppliers.status.update', $supplier), ['is_active' => false])
        ->assertRedirect();
    expect($supplier->fresh()->is_active)->toBeFalse();

    $this->actingAs($manager)
        ->patch(route('suppliers.status.update', $supplier), ['is_active' => true])
        ->assertRedirect();
    expect($supplier->fresh()->is_active)->toBeTrue();
});

test('deletes an unused supplier', function () {
    $manager = createSupplierManager();
    $supplier = Supplier::factory()->create();

    $this->actingAs($manager)
        ->delete(route('suppliers.destroy', $supplier))
        ->assertRedirect(route('suppliers.index'));

    $this->assertModelMissing($supplier);
});

test('deactivates a supplier instead of deleting it when products are associated', function () {
    $manager = createSupplierManager();
    $supplier = Supplier::factory()->create(['is_active' => true]);
    $supplier->products()->attach(Product::factory()->create());

    $this->actingAs($manager)
        ->delete(route('suppliers.destroy', $supplier))
        ->assertRedirect(route('suppliers.index'));

    $this->assertModelExists($supplier);
    expect($supplier->fresh()->is_active)->toBeFalse();
});

test('deactivates a supplier with inquiry history even after its catalog is empty', function () {
    $manager = createSupplierManager();
    $supplier = Supplier::factory()->create(['is_active' => true]);
    SupplierInquiry::factory()->create(['supplier_id' => $supplier, 'requested_by' => $manager]);

    $this->actingAs($manager)->delete(route('suppliers.destroy', $supplier))->assertRedirect(route('suppliers.index'));

    expect($supplier->fresh()->is_active)->toBeFalse();
});

test('stores a supplier logo and attachments', function () {
    Storage::fake('public');
    $manager = createSupplierManager();
    $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

    $this->actingAs($manager)
        ->post(route('suppliers.store'), validSupplierPayload([
            'logo' => UploadedFile::fake()->createWithContent('logo.png', $pngContent),
            'attachments' => [UploadedFile::fake()->createWithContent('cotizacion.pdf', "%PDF-1.4\n%%EOF")],
        ]))
        ->assertRedirect();

    $supplier = Supplier::query()->where('tax_id', '20609988776')->firstOrFail();
    expect($supplier->getMedia('logo'))->toHaveCount(1)
        ->and($supplier->getMedia('attachments'))->toHaveCount(1);
});

test('rejects unsupported supplier files', function () {
    Storage::fake('public');
    $manager = createSupplierManager();

    $this->actingAs($manager)
        ->post(route('suppliers.store'), validSupplierPayload([
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            'attachments' => [UploadedFile::fake()->create('program.exe', 10, 'application/octet-stream')],
        ]))
        ->assertSessionHasErrors(['logo', 'attachments.0']);

    expect(Supplier::query()->count())->toBe(0);
});

test('deletes only media owned by the selected supplier', function () {
    Storage::fake('public');
    $manager = createSupplierManager();
    $supplier = Supplier::factory()->create();
    $otherSupplier = Supplier::factory()->create();
    $attachment = $supplier
        ->addMedia(UploadedFile::fake()->createWithContent('document.pdf', "%PDF-1.4\n%%EOF"))
        ->toMediaCollection('attachments');

    $this->actingAs($manager)
        ->delete(route('suppliers.media.destroy', [$otherSupplier, $attachment]))
        ->assertNotFound();
    $this->assertModelExists($attachment);

    $this->actingAs($manager)
        ->delete(route('suppliers.media.destroy', [$supplier, $attachment]))
        ->assertRedirect();
    $this->assertModelMissing($attachment);
});
