<?php

namespace App\Actions\Cashier;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenCashRegister
{
    public function handle(User $user, string $openingAmount, ?string $notes = null): CashRegister
    {
        if (! is_numeric($openingAmount) || bccomp($openingAmount, '0', 2) === -1) {
            throw new \InvalidArgumentException('El monto de apertura debe ser un número válido.');
        }

        return DB::transaction(function () use ($user, $openingAmount, $notes): CashRegister {
            $alreadyOpen = CashRegister::query()
                ->where('status', CashRegisterStatus::Open)
                ->lockForUpdate()
                ->exists();

            if ($alreadyOpen) {
                throw ValidationException::withMessages([
                    'opening_amount' => 'Ya hay una caja abierta. Ciérrala antes de abrir otra.',
                ]);
            }

            return CashRegister::query()->create([
                'status' => CashRegisterStatus::Open,
                'opened_by' => $user->id,
                'opening_amount' => $openingAmount,
                'opened_at' => now(),
                'opening_notes' => $notes,
            ]);
        });
    }
}
