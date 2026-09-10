<?php

namespace App\Http\Controllers;

use App\Actions\Purchases\ReceivePurchaseOrder;
use App\Http\Requests\StorePurchaseReceiptRequest;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PurchaseReceiptController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StorePurchaseReceiptRequest $request, PurchaseOrder $purchaseOrder, ReceivePurchaseOrder $receivePurchaseOrder): RedirectResponse
    {
        $receipt = $receivePurchaseOrder->handle($purchaseOrder, $request->user(), $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => "RecepciÃ³n {$receipt->number} registrada; el stock fue actualizado."]);

        return to_route('purchases.orders.show', $purchaseOrder);
    }
}
