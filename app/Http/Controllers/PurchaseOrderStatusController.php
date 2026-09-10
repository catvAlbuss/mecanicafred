<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\UpdatePurchaseOrderStatusRequest;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PurchaseOrderStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdatePurchaseOrderStatusRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        DB::transaction(function () use ($request, $purchaseOrder): void {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);
            $action = $request->string('action')->toString();
            $nextStatus = match ($action) {
                'send' => PurchaseOrderStatus::Sent,
                'confirm' => PurchaseOrderStatus::Confirmed,
                'cancel' => PurchaseOrderStatus::Cancelled,
                default => throw new \LogicException('AcciÃ³n de pedido no reconocida.'),
            };
            $allowed = match ($order->status) {
                PurchaseOrderStatus::Draft => [PurchaseOrderStatus::Sent, PurchaseOrderStatus::Cancelled],
                PurchaseOrderStatus::Sent => [PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::Cancelled],
                PurchaseOrderStatus::Confirmed => [PurchaseOrderStatus::Cancelled],
                default => [],
            };
            if (! in_array($nextStatus, $allowed, true)) {
                throw ValidationException::withMessages(['action' => 'La transiciÃ³n solicitada no es vÃ¡lida para el estado actual.']);
            }
            $attributes = ['status' => $nextStatus];
            if ($nextStatus === PurchaseOrderStatus::Sent) {
                $attributes['ordered_at'] = now();
            }
            if ($nextStatus === PurchaseOrderStatus::Confirmed) {
                $attributes['approved_by'] = $request->user()->id;
            }
            if ($nextStatus === PurchaseOrderStatus::Cancelled) {
                $attributes['cancellation_reason'] = $request->string('reason')->trim()->toString();
            }
            $order->update($attributes);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado del pedido actualizado.']);

        return back();
    }
}
