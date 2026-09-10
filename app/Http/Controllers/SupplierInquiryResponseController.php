<?php

namespace App\Http\Controllers;

use App\Enums\SupplierInquiryStatus;
use App\Http\Requests\RespondSupplierInquiryRequest;
use App\Models\SupplierInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SupplierInquiryResponseController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RespondSupplierInquiryRequest $request, SupplierInquiry $inquiry): RedirectResponse
    {
        if ($inquiry->status !== SupplierInquiryStatus::Sent) {
            throw ValidationException::withMessages(['items' => 'Solo se puede responder una consulta enviada.']);
        }
        DB::transaction(function () use ($request, $inquiry): void {
            foreach ($request->array('items') as $response) {
                $available = filter_var($response['is_available'], FILTER_VALIDATE_BOOL);
                $inquiry->items()->whereKey($response['id'])->update([
                    'is_available' => $available,
                    'quantity_available' => $available ? $response['quantity_available'] : null,
                    'quoted_unit_cost' => $available ? $response['quoted_unit_cost'] : null,
                    'supplier_notes' => Arr::get($response, 'supplier_notes'),
                ]);
            }
            $inquiry->update(['status' => SupplierInquiryStatus::Answered, 'responded_at' => now(), 'valid_until' => $request->date('valid_until')]);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Respuesta del proveedor registrada.']);

        return back();
    }
}
