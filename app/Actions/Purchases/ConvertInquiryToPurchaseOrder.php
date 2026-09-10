<?php

namespace App\Actions\Purchases;

use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierInquiryStatus;
use App\Models\PurchaseOrder;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertInquiryToPurchaseOrder
{
    public function handle(SupplierInquiry $inquiry, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($inquiry, $user): PurchaseOrder {
            $lockedInquiry = SupplierInquiry::query()->lockForUpdate()->with(['items.product', 'purchaseOrder'])->findOrFail($inquiry->id);
            if ($lockedInquiry->purchaseOrder !== null) {
                return $lockedInquiry->purchaseOrder;
            }
            if ($lockedInquiry->status !== SupplierInquiryStatus::Answered) {
                throw ValidationException::withMessages(['inquiry' => 'Solo una consulta respondida puede convertirse en pedido.']);
            }
            $availableItems = $lockedInquiry->items->filter(fn (SupplierInquiryItem $item): bool => $this->hasPositiveQuote($item));
            if ($availableItems->isEmpty()) {
                throw ValidationException::withMessages(['inquiry' => 'La consulta no tiene productos disponibles para pedir.']);
            }
            $order = PurchaseOrder::query()->create(['supplier_id' => $lockedInquiry->supplier_id, 'supplier_inquiry_id' => $lockedInquiry->id, 'created_by' => $user->id, 'status' => PurchaseOrderStatus::Draft, 'currency' => 'PEN', 'subtotal' => '0', 'tax_rate' => '18', 'tax' => '0', 'total' => '0', 'notes' => "Creado desde la consulta {$lockedInquiry->number}."]);
            $total = '0.00';
            foreach ($availableItems as $item) {
                $availableQuantity = (string) $item->quantity_available;
                $requestedQuantity = (string) $item->quantity_requested;
                $unitCost = (string) $item->quoted_unit_cost;
                if (! is_numeric($availableQuantity) || ! is_numeric($unitCost)) {
                    throw new \LogicException('La cotización contiene cantidades o costos inválidos.');
                }
                $quantity = bccomp($availableQuantity, $requestedQuantity, 3) === -1 ? $availableQuantity : $requestedQuantity;
                $lineSubtotal = bcadd(bcmul($quantity, $unitCost, 4), '0.005', 2);
                $order->items()->create(['product_id' => $item->product_id, 'product_name' => $item->product->name, 'product_sku' => $item->product->sku, 'unit' => $item->product->unit, 'quantity_ordered' => $quantity, 'quantity_received' => '0', 'unit_cost' => $item->quoted_unit_cost, 'subtotal' => $lineSubtotal]);
                $total = bcadd($total, $lineSubtotal, 2);
            }
            $tax = bcadd(bcdiv(bcmul($total, '18', 4), '100', 4), '0.005', 2);
            $order->update(['subtotal' => $total, 'tax' => $tax, 'total' => bcadd($total, $tax, 2)]);

            return $order;
        });
    }

    private function hasPositiveQuote(SupplierInquiryItem $item): bool
    {
        $quantity = (string) $item->quantity_available;

        return $item->is_available && $item->quoted_unit_cost !== null && is_numeric($quantity) && bccomp($quantity, '0', 3) === 1;
    }
}
