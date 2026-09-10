<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductBarcodeLookupController extends Controller
{
    /**
     * Resolve a scanned barcode (or SKU) to the matching product so the
     * purchasing screens can add or receive the item without manual search.
     */
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Product::class);

        $code = $request->string('code')->trim()->toString();

        abort_if($code === '', 422, 'Indica un código para buscar.');

        $product = Product::query()
            ->with('category:id,name')
            ->where(fn (Builder $query) => $query->where('barcode', $code)->orWhere('sku', $code))
            ->first();

        abort_if($product === null, 404, 'No hay ningún producto con ese código.');

        return response()->json([
            'id' => $product->id,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'brand' => $product->brand,
            'unit' => $product->unit->value,
            'unit_label' => $product->unit->label(),
            'current_stock' => $product->current_stock,
            'last_purchase_cost' => $product->last_purchase_cost,
            'sale_price' => $product->sale_price,
            'is_active' => $product->is_active,
            'category_name' => $product->category->name,
        ]);
    }
}
