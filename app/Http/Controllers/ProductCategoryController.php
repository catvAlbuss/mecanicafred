<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ProductCategory::class);

        return Inertia::render('inventory/Categories', [
            'categories' => ProductCategory::query()->withCount('products')->orderBy('name')->get()->map(fn (ProductCategory $category) => [
                'id' => $category->id, 'name' => $category->name, 'type' => $category->type->value,
                'type_label' => $category->type->label(), 'description' => $category->description,
                'is_active' => $category->is_active, 'products_count' => $category->products_count,
            ]),
            'types' => array_map(fn (ProductType $type) => ['value' => $type->value, 'label' => $type->label()], ProductType::cases()),
            'canManage' => $request->user()->can('create', ProductCategory::class),
        ]);
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        ProductCategory::query()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría registrada correctamente.']);

        return to_route('inventory.categories.index');
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría actualizada correctamente.']);

        return to_route('inventory.categories.index');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        Gate::authorize('delete', $category);
        if ($category->products()->exists()) {
            $category->update(['is_active' => false]);
            $message = 'La categoría tiene productos y fue desactivada.';
        } else {
            $category->delete();
            $message = 'Categoría eliminada correctamente.';
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('inventory.categories.index');
    }
}
