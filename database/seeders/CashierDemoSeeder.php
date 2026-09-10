<?php

namespace Database\Seeders;

use App\Actions\Cashier\RegisterCashTransaction;
use App\Actions\Cashier\RegisterSale;
use App\Enums\CashRegisterStatus;
use App\Enums\CashTransactionCategory;
use App\Enums\CashTransactionType;
use App\Enums\PaymentMethod;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CashierDemoSeeder extends Seeder
{
    /**
     * Seed an open cash register with a couple of demo sales and an expense
     * so the store module has data on a fresh install.
     */
    public function run(RegisterSale $registerSale, RegisterCashTransaction $registerCashTransaction): void
    {
        $user = User::query()->where('email', 'admin@fredyracing.test')->first();

        if ($user === null || CashRegister::query()->exists()) {
            return;
        }

        $products = Product::query()->whereIn('sku', ['LUB-001', 'REP-003', 'CON-002'])->get()->keyBy('sku');

        if ($products->count() < 3) {
            return;
        }

        $register = CashRegister::query()->create([
            'status' => CashRegisterStatus::Open,
            'opened_by' => $user->id,
            'opening_amount' => '150.00',
            'opened_at' => now()->subHours(3),
            'opening_notes' => 'Apertura de demostración.',
        ]);

        $registerSale->handle($register, $user, [
            'idempotency_key' => (string) Str::uuid(),
            'payment_method' => PaymentMethod::Cash->value,
            'customer_name' => 'Cliente mostrador',
            'items' => [
                ['product_id' => $products['LUB-001']->id, 'quantity' => '2.000', 'unit_price' => (string) $products['LUB-001']->sale_price],
                ['product_id' => $products['REP-003']->id, 'quantity' => '1.000', 'unit_price' => (string) $products['REP-003']->sale_price],
            ],
        ]);

        $registerSale->handle($register, $user, [
            'idempotency_key' => (string) Str::uuid(),
            'payment_method' => PaymentMethod::Yape->value,
            'items' => [
                ['product_id' => $products['CON-002']->id, 'quantity' => '3.000', 'unit_price' => (string) $products['CON-002']->sale_price],
            ],
        ]);

        $registerCashTransaction->handle($register, $user, [
            'type' => CashTransactionType::Expense,
            'category' => CashTransactionCategory::OperatingExpense,
            'payment_method' => PaymentMethod::Cash,
            'amount' => '25.00',
            'description' => 'Compra de agua y café para el taller',
            'reference' => null,
        ]);
    }
}
