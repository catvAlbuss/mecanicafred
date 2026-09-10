<?php

namespace App\Http\Controllers;

use App\Actions\Cashier\RegisterCashTransaction;
use App\Enums\CashTransactionCategory;
use App\Enums\CashTransactionType;
use App\Enums\PaymentMethod;
use App\Http\Requests\StoreCashTransactionRequest;
use App\Models\CashRegister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CashTransactionController extends Controller
{
    public function store(StoreCashTransactionRequest $request, RegisterCashTransaction $registerCashTransaction): RedirectResponse
    {
        $register = CashRegister::currentOpen();

        if ($register === null) {
            throw ValidationException::withMessages(['amount' => 'Abre una caja para registrar movimientos.']);
        }

        $registerCashTransaction->handle($register, $request->user(), [
            'type' => CashTransactionType::from($request->string('type')->toString()),
            'category' => CashTransactionCategory::from($request->string('category')->toString()),
            'payment_method' => PaymentMethod::from($request->string('payment_method')->toString()),
            'amount' => $request->string('amount')->toString(),
            'description' => $request->string('description')->toString(),
            'reference' => $request->filled('reference') ? $request->string('reference')->toString() : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Movimiento de caja registrado.']);

        return to_route('cashier.index');
    }
}
