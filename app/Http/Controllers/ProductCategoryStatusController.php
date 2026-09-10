<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductCategoryStatusRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProductCategoryStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateProductCategoryStatusRequest $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => $category->is_active ? 'Categoría activada.' : 'Categoría desactivada.']);

        return back();
    }
}
