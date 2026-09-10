<?php

namespace App\Http\Controllers;

use App\Enums\SupplierAvailabilityStatus;
use App\Http\Requests\StoreSupplierCatalogProductRequest;
use App\Http\Requests\UpdateSupplierCatalogProductRequest;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierCatalogController extends Controller
{
    public function index(Supplier $supplier): Response
    {
        Gate::authorize('view', $supplier);
        $supplier->load(['products.category']);
        $catalogProductIds = $supplier->products->modelKeys();

        return Inertia::render('suppliers/Catalog', [
            'supplier' => ['id' => $supplier->id, 'name' => $supplier->trade_name ?: $supplier->business_name],
            'catalog' => $supplier->products->sortBy('name')->values()->map(fn (Product $product): array => [
                'id' => $product->id, 'sku' => $product->sku, 'name' => $product->name, 'category' => $product->category->name,
                'supplier_sku' => data_get($product->getRelation('pivot'), 'supplier_sku'), 'last_unit_cost' => data_get($product->getRelation('pivot'), 'last_unit_cost'),
                'lead_time_days' => data_get($product->getRelation('pivot'), 'lead_time_days'), 'availability_status' => data_get($product->getRelation('pivot'), 'availability_status'),
                'available_quantity' => data_get($product->getRelation('pivot'), 'available_quantity'), 'last_checked_at' => data_get($product->getRelation('pivot'), 'last_checked_at'),
                'is_preferred' => (bool) data_get($product->getRelation('pivot'), 'is_preferred'),
            ]),
            'availableProducts' => Product::query()->select(['id', 'sku', 'name'])->where('is_active', true)->whereNotIn('id', $catalogProductIds)->orderBy('name')->get(),
            'availabilityOptions' => array_map(fn (SupplierAvailabilityStatus $status) => ['value' => $status->value, 'label' => $status->label()], SupplierAvailabilityStatus::cases()),
            'canManage' => Gate::allows('update', $supplier),
        ]);
    }

    public function store(StoreSupplierCatalogProductRequest $request, Supplier $supplier): RedirectResponse
    {
        $attributes = $request->safe()->except('product_id');
        $attributes['last_checked_at'] = now();
        $supplier->products()->attach($request->integer('product_id'), $attributes);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto asociado al proveedor.']);

        return back();
    }

    public function update(UpdateSupplierCatalogProductRequest $request, Supplier $supplier, Product $product): RedirectResponse
    {
        abort_unless($supplier->products()->whereKey($product->id)->exists(), 404);
        $attributes = $request->safe()->except('product_id');
        $attributes['last_checked_at'] = now();
        $supplier->products()->updateExistingPivot($product->id, $attributes);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Oferta del proveedor actualizada.']);

        return back();
    }

    public function destroy(Supplier $supplier, Product $product): RedirectResponse
    {
        Gate::authorize('update', $supplier);
        abort_unless($supplier->products()->whereKey($product->id)->exists(), 404);
        $supplier->products()->detach($product->id);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto retirado del catálogo.']);

        return back();
    }
}
