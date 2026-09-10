<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\AdjustProductStock;
use App\Http\Requests\AdjustProductStockRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InventoryAdjustmentController extends Controller
{
    public function __invoke(AdjustProductStockRequest $request, Product $product, AdjustProductStock $adjustStock): RedirectResponse
    {
        $adjustStock->handle($product, $request->user(), $request->string('direction')->toString(), $request->string('quantity')->toString(), $request->string('reason')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Stock ajustado y movimiento registrado.']);

        return to_route('inventory.products.show', $product);
    }
}
