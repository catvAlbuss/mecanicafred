<?php

use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function movementCashier(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(['caja.ver', 'caja.registrar-movimiento']);

    return $user;
}

test('registers a manual expense against the open register', function () {
    $user = movementCashier();
    $register = CashRegister::factory()->create(['opened_by' => $user]);

    $this->actingAs($user)->post(route('cashier.transactions.store'), [
        'type' => 'expense',
        'category' => 'supplier_payment',
        'payment_method' => 'cash',
        'amount' => '350.00',
        'description' => 'Abono pedido lubricantes',
        'reference' => 'F001-2280',
    ])->assertRedirect(route('cashier.index'));

    $transaction = CashTransaction::query()->firstOrFail();
    expect($transaction->cash_register_id)->toBe($register->id)
        ->and($transaction->category->value)->toBe('supplier_payment')
        ->and((float) $transaction->amount)->toBe(350.0)
        ->and($transaction->reference)->toBe('F001-2280');
});

test('rejects a category that does not match the movement type', function () {
    $user = movementCashier();
    CashRegister::factory()->create(['opened_by' => $user]);

    $this->actingAs($user)->post(route('cashier.transactions.store'), [
        'type' => 'income',
        'category' => 'operating_expense',
        'payment_method' => 'cash',
        'amount' => '10.00',
        'description' => 'Prueba',
    ])->assertSessionHasErrors('category');

    expect(CashTransaction::query()->count())->toBe(0);
});

test('does not allow selecting the product sale category by hand', function () {
    $user = movementCashier();
    CashRegister::factory()->create(['opened_by' => $user]);

    $this->actingAs($user)->post(route('cashier.transactions.store'), [
        'type' => 'income',
        'category' => 'product_sale',
        'payment_method' => 'cash',
        'amount' => '10.00',
        'description' => 'Prueba',
    ])->assertSessionHasErrors('category');
});

test('rejects a movement when no register is open', function () {
    $this->actingAs(movementCashier())->post(route('cashier.transactions.store'), [
        'type' => 'expense',
        'category' => 'operating_expense',
        'payment_method' => 'cash',
        'amount' => '10.00',
        'description' => 'Prueba',
    ])->assertForbidden();
});

test('cash transactions are immutable', function () {
    $transaction = CashTransaction::factory()->create();

    expect(fn () => $transaction->update(['amount' => '1.00']))->toThrow(LogicException::class)
        ->and(fn () => $transaction->delete())->toThrow(LogicException::class);
});
