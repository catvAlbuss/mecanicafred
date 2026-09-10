<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductStatusRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProductStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateProductStatusRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => $product->is_active ? 'Producto activado.' : 'Producto desactivado.']);

        return back();
    }
}
