<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $inventory = null;

        if ($request->user()->can('viewAny', Product::class)) {
            $totals = Product::query()
                ->selectRaw('COALESCE(SUM(current_stock * COALESCE(last_purchase_cost, 0)), 0) as stock_cost')
                ->selectRaw('COALESCE(SUM(current_stock * COALESCE(sale_price, 0)), 0) as stock_value')
                ->first();

            $inventory = [
                'total' => Product::query()->count(),
                'available' => Product::query()->whereColumn('current_stock', '>', 'minimum_stock')->count(),
                'low' => Product::query()->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
                'out' => Product::query()->where('current_stock', '<=', 0)->count(),
                'stock_cost' => $totals->stock_cost,
                'stock_value' => $totals->stock_value,
                'attention_products' => Product::query()
                    ->select(['id', 'name', 'sku', 'current_stock', 'minimum_stock', 'unit'])
                    ->whereColumn('current_stock', '<=', 'minimum_stock')
                    ->orderBy('current_stock')
                    ->orderBy('name')
                    ->limit(5)
                    ->get()
                    ->map(fn (Product $product): array => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'current_stock' => $product->current_stock,
                        'minimum_stock' => $product->minimum_stock,
                        'unit_label' => $product->unit->label(),
                        'is_out' => bccomp((string) $product->current_stock, '0', 3) !== 1,
                    ])->values(),
                'recent_movements' => InventoryMovement::query()
                    ->with('product:id,name,sku')
                    ->latest('occurred_at')
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (InventoryMovement $movement): array => [
                        'id' => $movement->id,
                        'product_name' => $movement->product->name,
                        'type_label' => $movement->type->label(),
                        'quantity' => $movement->quantity,
                        'occurred_at' => $movement->occurred_at->toIso8601String(),
                    ])->values(),
            ];
        }

        return Inertia::render('Dashboard', compact('inventory'));
    }
}
