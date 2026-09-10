<?php

namespace App\Http\Controllers;

use App\Actions\Cashier\OpenCashRegister;
use App\Http\Requests\OpenCashRegisterRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OpenCashRegisterController extends Controller
{
    public function __invoke(OpenCashRegisterRequest $request, OpenCashRegister $openCashRegister): RedirectResponse
    {
        $register = $openCashRegister->handle(
            $request->user(),
            $request->string('opening_amount')->toString(),
            $request->filled('notes') ? $request->string('notes')->toString() : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Caja {$register->number} abierta."]);

        return to_route('cashier.index');
    }
}
