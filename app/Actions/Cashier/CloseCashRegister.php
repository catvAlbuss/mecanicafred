<?php

namespace App\Actions\Cashier;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseCashRegister
{
    public function handle(CashRegister $register, User $user, string $countedCashAmount, ?string $notes = null): CashRegister
    {
        if (! is_numeric($countedCashAmount) || bccomp($countedCashAmount, '0', 2) === -1) {
            throw new \InvalidArgumentException('El monto contado debe ser un número válido.');
        }

        return DB::transaction(function () use ($register, $user, $countedCashAmount, $notes): CashRegister {
            $locked = CashRegister::query()->lockForUpdate()->findOrFail($register->id);

            if (! $locked->isOpen()) {
                throw ValidationException::withMessages(['counted_cash_amount' => 'La caja ya está cerrada.']);
            }

            $expected = $locked->summary()['expected_cash'];
            $difference = bcsub($countedCashAmount, $expected, 2);

            $locked->update([
                'status' => CashRegisterStatus::Closed,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'expected_cash_amount' => $expected,
                'counted_cash_amount' => $countedCashAmount,
                'difference' => $difference,
                'closing_notes' => $notes,
            ]);

            return $locked;
        });
    }
}
