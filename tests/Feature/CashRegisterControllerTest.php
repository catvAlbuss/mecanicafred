<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function cashier(array $permissions = ['caja.ver', 'caja.abrir', 'caja.cerrar', 'caja.registrar-movimiento']): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('opens a register and blocks a second open register', function () {
    $user = cashier();

    $this->actingAs($user)->post(route('cashier.open'), ['opening_amount' => '100.00'])
        ->assertRedirect(route('cashier.index'));

    $register = CashRegister::query()->firstOrFail();
    expect($register->status)->toBe(CashRegisterStatus::Open)
        ->and($register->number)->toMatch('/^CAJA-\d{4}-\d{6}$/')
        ->and((float) $register->opening_amount)->toBe(100.0);

    $this->actingAs($user)->post(route('cashier.open'), ['opening_amount' => '50.00'])
        ->assertSessionHasErrors('opening_amount');

    expect(CashRegister::query()->count())->toBe(1);
});

test('closes a register computing the expected cash and the difference', function () {
    $user = cashier();
    $register = CashRegister::factory()->create(['opened_by' => $user, 'opening_amount' => '100.00']);

    // 40 cash in, 15 cash out, 30 yape in (does not touch the drawer)
    $register->transactions()->createMany([
        ['user_id' => $user->id, 'type' => 'income', 'category' => 'product_sale', 'payment_method' => 'cash', 'amount' => '40.00', 'description' => 'Venta', 'occurred_at' => now()],
        ['user_id' => $user->id, 'type' => 'expense', 'category' => 'operating_expense', 'payment_method' => 'cash', 'amount' => '15.00', 'description' => 'Gasto', 'occurred_at' => now()],
        ['user_id' => $user->id, 'type' => 'income', 'category' => 'product_sale', 'payment_method' => 'yape', 'amount' => '30.00', 'description' => 'Venta', 'occurred_at' => now()],
    ]);

    $this->actingAs($user)->post(route('cashier.close'), ['counted_cash_amount' => '120.00'])
        ->assertRedirect(route('cashier.index'));

    $register->refresh();
    expect($register->status)->toBe(CashRegisterStatus::Closed)
        ->and((float) $register->expected_cash_amount)->toBe(125.0)
        ->and((float) $register->counted_cash_amount)->toBe(120.0)
        ->and((float) $register->difference)->toBe(-5.0);
});

test('rejects closing when no register is open', function () {
    $this->actingAs(cashier())->post(route('cashier.close'), ['counted_cash_amount' => '10.00'])
        ->assertForbidden();
});

test('shows the daily screen with the open register summary', function () {
    $user = cashier();
    CashRegister::factory()->create(['opened_by' => $user, 'opening_amount' => '80.00']);

    $this->actingAs($user)->get(route('cashier.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('cashier/Index')
            ->where('register.summary.expected_cash', '80.00')
            ->where('can.close', true));
});

test('forbids the cashier screens without the permission', function () {
    $this->actingAs(User::factory()->create())->get(route('cashier.index'))->assertForbidden();
    $this->actingAs(User::factory()->create())->post(route('cashier.open'), ['opening_amount' => '1'])->assertForbidden();
});
