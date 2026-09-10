<?php

namespace App\Http\Controllers;

use App\Actions\Cashier\CancelSale;
use App\Http\Requests\CancelSaleRequest;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SaleCancellationController extends Controller
{
    public function __invoke(CancelSaleRequest $request, Sale $sale, CancelSale $cancelSale): RedirectResponse
    {
        $cancelSale->handle($sale, $request->user(), $request->string('reason')->toString());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Venta {$sale->number} anulada. El stock y el dinero fueron revertidos.",
        ]);

        return to_route('sales.show', $sale);
    }
}
