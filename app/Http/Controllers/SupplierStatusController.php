<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSupplierStatusRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SupplierStatusController extends Controller
{
    public function __invoke(UpdateSupplierStatusRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $supplier->is_active
                ? 'Proveedor activado correctamente.'
                : 'Proveedor desactivado correctamente.',
        ]);

        return back();
    }
}
