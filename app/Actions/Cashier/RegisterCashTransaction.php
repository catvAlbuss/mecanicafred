<?php

namespace App\Actions\Cashier;

use App\Enums\CashTransactionCategory;
use App\Enums\CashTransactionType;
use App\Enums\PaymentMethod;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterCashTransaction
{
    /**
     * Register a manual cash movement (an expense, a withdrawal, a deposit…)
     * against the currently open register.
     *
     * @param  array{type: CashTransactionType, category: CashTransactionCategory, payment_method: PaymentMethod, amount: string, description: string, reference?: string|null}  $data
     */
    public function handle(CashRegister $register, User $user, array $data): CashTransaction
    {
        return DB::transaction(function () use ($register, $user, $data): CashTransaction {
            $locked = CashRegister::query()->lockForUpdate()->findOrFail($register->id);

            if (! $locked->isOpen()) {
                throw ValidationException::withMessages(['amount' => 'La caja está cerrada.']);
            }

            return $locked->transactions()->create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'category' => $data['category'],
                'payment_method' => $data['payment_method'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'reference' => $data['reference'] ?? null,
                'occurred_at' => now(),
            ]);
        });
    }
}
