<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SaleProductSearchController extends Controller
{
    /**
     * Search active products for the point-of-sale screen.
     */
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('create', Sale::class);

        $term = $request->string('q')->trim()->toString();

        $products = Product::query()
            ->where('is_active', true)
            ->when($term !== '', function (Builder $query) use ($term): void {
                $value = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn (Builder $nested) => $nested->where('name', 'like', $value)
                    ->orWhere('sku', 'like', $value)
                    ->orWhere('barcode', 'like', $value)
                    ->orWhere('brand', 'like', $value));
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'sku', 'barcode', 'name', 'brand', 'unit', 'current_stock', 'sale_price'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'brand' => $product->brand,
                'unit_label' => $product->unit->label(),
                'current_stock' => $product->current_stock,
                'sale_price' => $product->sale_price,
            ]);

        return response()->json(['products' => $products]);
    }
}
