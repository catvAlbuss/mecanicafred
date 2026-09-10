<?php

namespace App\Http\Controllers;

use App\Actions\Cashier\CloseCashRegister;
use App\Http\Requests\CloseCashRegisterRequest;
use App\Models\CashRegister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CloseCashRegisterController extends Controller
{
    public function __invoke(CloseCashRegisterRequest $request, CloseCashRegister $closeCashRegister): RedirectResponse
    {
        $register = CashRegister::currentOpen();

        if ($register === null) {
            throw ValidationException::withMessages(['counted_cash_amount' => 'No hay ninguna caja abierta.']);
        }

        $closed = $closeCashRegister->handle(
            $register,
            $request->user(),
            $request->string('counted_cash_amount')->toString(),
            $request->filled('notes') ? $request->string('notes')->toString() : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Caja {$closed->number} cerrada. Diferencia: S/ {$closed->difference}.",
        ]);

        return to_route('cashier.index');
    }
}
