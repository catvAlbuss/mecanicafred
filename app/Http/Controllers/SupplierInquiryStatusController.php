<?php

namespace App\Http\Controllers;

use App\Enums\SupplierInquiryStatus;
use App\Http\Requests\UpdateSupplierInquiryStatusRequest;
use App\Models\SupplierInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SupplierInquiryStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateSupplierInquiryStatusRequest $request, SupplierInquiry $inquiry): RedirectResponse
    {
        $action = $request->string('action')->toString();
        $nextStatus = match ($action) {
            'send' => SupplierInquiryStatus::Sent,
            'close' => SupplierInquiryStatus::Closed,
            'cancel' => SupplierInquiryStatus::Cancelled,
            default => throw new \LogicException('Acción de consulta no reconocida.'),
        };
        $allowed = match ($inquiry->status) {
            SupplierInquiryStatus::Draft => [SupplierInquiryStatus::Sent, SupplierInquiryStatus::Cancelled],
            SupplierInquiryStatus::Sent => [SupplierInquiryStatus::Cancelled],
            SupplierInquiryStatus::Answered => [SupplierInquiryStatus::Closed, SupplierInquiryStatus::Cancelled],
            default => [],
        };
        if (! in_array($nextStatus, $allowed, true)) {
            throw ValidationException::withMessages(['action' => 'La transición solicitada no es válida para el estado actual.']);
        }
        $attributes = ['status' => $nextStatus];
        if ($nextStatus === SupplierInquiryStatus::Sent) {
            $attributes['requested_at'] = now();
        }
        if ($nextStatus === SupplierInquiryStatus::Cancelled && $request->filled('reason')) {
            $attributes['notes'] = trim(($inquiry->notes ? $inquiry->notes."\n" : '').'Cancelación: '.$request->string('reason')->toString());
        }
        $inquiry->update($attributes);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado de la consulta actualizado.']);

        return back();
    }
}
