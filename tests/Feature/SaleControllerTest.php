<?php

use App\Actions\Cashier\RegisterSale;
use App\Enums\InventoryMovementType;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function seller(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(['ventas.ver', 'ventas.registrar', 'ventas.anular']);

    return $user;
}

test('registers a sale, discounts stock and books the cash income', function () {
    $user = seller();
    $register = CashRegister::factory()->create(['opened_by' => $user]);
    $oil = Product::factory()->create(['current_stock' => '10.000', 'last_purchase_cost' => '22.5000']);
    $filter = Product::factory()->create(['current_stock' => '4.000', 'last_purchase_cost' => '9.0000']);

    $this->actingAs($user)->post(route('sales.store'), [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'cash',
        'discount' => '5.00',
        'customer_name' => 'Cliente mostrador',
        'items' => [
            ['product_id' => $oil->id, 'quantity' => '2.000', 'unit_price' => '32.00'],
            ['product_id' => $filter->id, 'quantity' => '1.000', 'unit_price' => '15.00'],
        ],
    ])->assertRedirect();

    $sale = Sale::query()->firstOrFail();
    expect($sale->number)->toMatch('/^VTA-\d{4}-\d{6}$/')
        ->and((float) $sale->subtotal)->toBe(79.0)
        ->and((float) $sale->discount)->toBe(5.0)
        ->and((float) $sale->total)->toBe(74.0)
        ->and($oil->fresh()->current_stock)->toBe('8.000')
        ->and($filter->fresh()->current_stock)->toBe('3.000');

    $movement = InventoryMovement::query()->where('product_id', $oil->id)->firstOrFail();
    expect($movement->type)->toBe(InventoryMovementType::Sale)
        ->and($movement->quantity)->toBe('-2.000')
        ->and($movement->stock_after)->toBe('8.000')
        ->and($movement->reference->is($sale))->toBeTrue();

    $transaction = CashTransaction::query()->firstOrFail();
    expect($transaction->cash_register_id)->toBe($register->id)
        ->and($transaction->category->value)->toBe('product_sale')
        ->and((float) $transaction->amount)->toBe(74.0);

    $sale->items->each(fn ($item) => expect((float) $item->unit_cost)->toBeGreaterThan(0));
});

test('finds a product in sales by its alphanumeric barcode', function () {
    $user = seller();
    $product = Product::factory()->create([
        'barcode' => 'ACEITE-10W40-A1',
        'name' => 'Aceite de motor',
    ]);

    $this->actingAs($user)
        ->getJson(route('sales.products.search', ['q' => 'ACEITE-10W40-A1']))
        ->assertOk()
        ->assertJsonPath('products.0.id', $product->id)
        ->assertJsonPath('products.0.barcode', 'ACEITE-10W40-A1');
});

test('is idempotent on a retried submit', function () {
    $user = seller();
    CashRegister::factory()->create(['opened_by' => $user]);
    $product = Product::factory()->create(['current_stock' => '10.000']);
    $payload = [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => '3.000', 'unit_price' => '10.00']],
    ];

    $this->actingAs($user)->post(route('sales.store'), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('sales.store'), $payload)->assertRedirect();

    expect(Sale::query()->count())->toBe(1)
        ->and(InventoryMovement::query()->count())->toBe(1)
        ->and($product->fresh()->current_stock)->toBe('7.000');
});

test('rejects a sale over the available stock and rolls everything back', function () {
    $user = seller();
    CashRegister::factory()->create(['opened_by' => $user]);
    $product = Product::factory()->create(['current_stock' => '2.000']);

    $this->actingAs($user)->post(route('sales.store'), [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => '3.000', 'unit_price' => '10.00']],
    ])->assertSessionHasErrors('items');

    expect(Sale::query()->count())->toBe(0)
        ->and(InventoryMovement::query()->count())->toBe(0)
        ->and(CashTransaction::query()->count())->toBe(0)
        ->and($product->fresh()->current_stock)->toBe('2.000');
});

test('requires an open register to sell', function () {
    $user = seller();
    $product = Product::factory()->create(['current_stock' => '5.000']);

    $this->actingAs($user)->post(route('sales.store'), [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => '1.000', 'unit_price' => '10.00']],
    ])->assertSessionHasErrors('items');

    expect(Sale::query()->count())->toBe(0);
});

test('rejects an inactive product', function () {
    $user = seller();
    CashRegister::factory()->create(['opened_by' => $user]);
    $product = Product::factory()->inactive()->create(['current_stock' => '5.000']);

    $this->actingAs($user)->post(route('sales.store'), [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => '1.000', 'unit_price' => '10.00']],
    ])->assertSessionHasErrors('items.0.product_id');

    expect(Sale::query()->count())->toBe(0);
});

test('the sale action itself refuses a product deactivated after validation', function () {
    $user = seller();
    CashRegister::factory()->create(['opened_by' => $user]);
    $product = Product::factory()->create(['current_stock' => '5.000']);

    $product->update(['is_active' => false]);

    expect(fn () => app(RegisterSale::class)->handle(
        CashRegister::currentOpen(),
        $user,
        [
            'idempotency_key' => fake()->uuid(),
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => '1.000', 'unit_price' => '10.00']],
        ],
    ))->toThrow(ValidationException::class);
});

test('shows the sale ticket', function () {
    $user = seller();
    $register = CashRegister::factory()->create(['opened_by' => $user]);
    $product = Product::factory()->create(['current_stock' => '5.000']);
    $this->actingAs($user)->post(route('sales.store'), [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'yape',
        'items' => [['product_id' => $product->id, 'quantity' => '1.000', 'unit_price' => '12.00']],
    ]);
    $sale = Sale::query()->firstOrFail();

    $this->actingAs($user)->get(route('sales.show', $sale))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('sales/Show')
            ->where('sale.number', $sale->number)
            ->where('sale.payment_method_label', 'Yape')
            ->has('sale.items', 1));
});

test('forbids selling without the permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('ventas.ver');
    CashRegister::factory()->create();
    $product = Product::factory()->create(['current_stock' => '5.000']);

    $this->actingAs($user)->post(route('sales.store'), [
        'idempotency_key' => fake()->uuid(),
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => '1.000', 'unit_price' => '10.00']],
    ])->assertForbidden();
});
