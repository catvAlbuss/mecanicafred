<?php

namespace App\Actions\Purchases;

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivePurchaseOrder
{
    /** @param array<string, mixed> $data */
    public function handle(PurchaseOrder $purchaseOrder, User $user, array $data): PurchaseReceipt
    {
        return DB::transaction(function () use ($purchaseOrder, $user, $data): PurchaseReceipt {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);
            $idempotencyKey = (string) $data['idempotency_key'];
            $existingReceipt = PurchaseReceipt::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existingReceipt !== null) {
                if ($existingReceipt->purchase_order_id !== $order->id) {
                    throw ValidationException::withMessages(['idempotency_key' => 'La clave de recepciÃ³n ya fue utilizada.']);
                }

                return $existingReceipt;
            }
            if (! in_array($order->status, [PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::PartiallyReceived], true)) {
                throw ValidationException::withMessages(['order' => 'Este pedido ya no admite recepciones.']);
            }
            $submittedItems = $data['items'] ?? [];
            $attachments = $data['attachments'] ?? [];
            if (! is_array($submittedItems) || ! is_array($attachments)) {
                throw new \LogicException('Los datos de recepciÃ³n no tienen el formato esperado.');
            }
            $quantities = [];
            $unitCosts = [];
            foreach ($submittedItems as $submittedItem) {
                if (! is_array($submittedItem)) {
                    throw new \LogicException('Una lÃ­nea de recepciÃ³n no tiene el formato esperado.');
                }
                $quantity = $this->numericString($submittedItem['quantity_received'] ?? null);
                if (bccomp($quantity, '0', 3) === 1) {
                    $orderItemId = (int) $submittedItem['purchase_order_item_id'];
                    $quantities[$orderItemId] = $quantity;
                    if (($submittedItem['unit_cost'] ?? null) !== null && $submittedItem['unit_cost'] !== '') {
                        $unitCosts[$orderItemId] = $this->numericString($submittedItem['unit_cost']);
                    }
                }
            }
            if ($quantities === []) {
                throw ValidationException::withMessages(['items' => 'Ingresa al menos una cantidad recibida mayor que cero.']);
            }
            ksort($quantities);
            $orderItems = PurchaseOrderItem::query()->where('purchase_order_id', $order->id)->whereIn('id', array_keys($quantities))->lockForUpdate()->get()->keyBy('id');
            if ($orderItems->count() !== count($quantities)) {
                throw ValidationException::withMessages(['items' => 'Una lÃ­nea no pertenece al pedido.']);
            }
            $productIds = $orderItems->pluck('product_id')->sort()->values()->all();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $receipt = PurchaseReceipt::query()->create([
                'idempotency_key' => $idempotencyKey,
                'purchase_order_id' => $order->id,
                'received_by' => $user->id,
                'received_at' => $data['received_at'],
                'supplier_document_number' => $data['supplier_document_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($quantities as $orderItemId => $quantity) {
                $orderItem = $orderItems->get($orderItemId);
                if (! $orderItem instanceof PurchaseOrderItem) {
                    throw new \LogicException('No se encontrÃ³ una lÃ­nea bloqueada del pedido.');
                }
                $pendingQuantity = bcsub((string) $orderItem->quantity_ordered, (string) $orderItem->quantity_received, 3);
                if (bccomp($quantity, $pendingQuantity, 3) === 1) {
                    throw ValidationException::withMessages(['items' => "La cantidad supera el saldo pendiente de {$orderItem->product_name} ({$pendingQuantity})."]);
                }
                $product = $products->get($orderItem->product_id);
                if (! $product instanceof Product) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos ya no existe.']);
                }
                $unitCost = $unitCosts[$orderItemId] ?? (string) $orderItem->unit_cost;
                $stockBefore = (string) $product->current_stock;
                $stockAfter = bcadd($stockBefore, $quantity, 3);
                $receipt->items()->create(['purchase_order_item_id' => $orderItem->id, 'product_id' => $product->id, 'quantity_received' => $quantity, 'unit_cost' => $unitCost]);
                $product->update(['current_stock' => $stockAfter, 'last_purchase_cost' => $unitCost]);
                $product->suppliers()->updateExistingPivot($order->supplier_id, ['last_unit_cost' => $unitCost, 'last_checked_at' => now()]);
                $orderItem->update(['quantity_received' => bcadd((string) $orderItem->quantity_received, $quantity, 3)]);
                $receipt->inventoryMovements()->create(['product_id' => $product->id, 'user_id' => $user->id, 'type' => InventoryMovementType::Receipt, 'quantity' => $quantity, 'stock_before' => $stockBefore, 'stock_after' => $stockAfter, 'reason' => "RecepciÃ³n {$receipt->number} del pedido {$order->number}", 'occurred_at' => $data['received_at']]);
            }
            $hasPendingItems = PurchaseOrderItem::query()->where('purchase_order_id', $order->id)->whereColumn('quantity_received', '<', 'quantity_ordered')->exists();
            $order->update(['status' => $hasPendingItems ? PurchaseOrderStatus::PartiallyReceived : PurchaseOrderStatus::Received, 'received_at' => $hasPendingItems ? null : $data['received_at']]);
            foreach ($attachments as $attachment) {
                if ($attachment instanceof UploadedFile) {
                    $receipt->addMedia($attachment)->toMediaCollection('attachments');
                }
            }

            return $receipt;
        });
    }

    /** @return numeric-string */
    private function numericString(mixed $value): string
    {
        if (! is_numeric($value)) {
            throw new \LogicException('La recepciÃ³n contiene una cantidad invÃ¡lida.');
        }

        return (string) $value;
    }
}
