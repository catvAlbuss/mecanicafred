<?php

namespace App\Http\Controllers;

use App\Actions\Purchases\ConvertInquiryToPurchaseOrder;
use App\Models\SupplierInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SupplierInquiryConversionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, SupplierInquiry $inquiry, ConvertInquiryToPurchaseOrder $convert): RedirectResponse
    {
        Gate::authorize('convert', $inquiry);
        $order = $convert->handle($inquiry, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => "Borrador {$order->number} creado correctamente."]);

        return back();
    }
}
