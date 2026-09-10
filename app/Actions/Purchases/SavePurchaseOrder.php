<?php

namespace App\Actions\Purchases;

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavePurchaseOrder
{
    /** @param array<string, mixed> $data */
    public function handle(array $data, User $user, ?PurchaseOrder $order = null): PurchaseOrder
    {
        if ($order !== null && $order->status !== PurchaseOrderStatus::Draft) {
            throw ValidationException::withMessages(['order' => 'Solo los pedidos en borrador pueden modificarse.']);
        }

        return DB::transaction(function () use ($data, $user, $order): PurchaseOrder {
            $items = $data['items'] ?? [];
            $attachments = $data['attachments'] ?? [];
            unset($data['items'], $data['attachments']);
            if (! is_array($items) || ! is_array($attachments)) {
                throw new \LogicException('Los datos del pedido no tienen el formato esperado.');
            }
            $productIds = [];
            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['product_id'])) {
                    throw new \LogicException('Una lÃ­nea del pedido no tiene el formato esperado.');
                }
                $productIds[] = (int) $item['product_id'];
            }
            $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');
            $subtotal = '0.00';
            $preparedItems = [];
            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                if (! $product instanceof Product) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos ya no estÃ¡ disponible.']);
                }
                $quantity = $this->numericString($item['quantity_ordered'] ?? null);
                $unitCost = $this->numericString($item['unit_cost'] ?? null);
                $lineSubtotal = bcadd(bcmul($quantity, $unitCost, 4), '0.005', 2);
                $subtotal = bcadd($subtotal, $lineSubtotal, 2);
                $preparedItems[] = ['product_id' => $product->id, 'product_name' => $product->name, 'product_sku' => $product->sku, 'unit' => $product->unit, 'quantity_ordered' => $quantity, 'quantity_received' => '0', 'unit_cost' => $unitCost, 'subtotal' => $lineSubtotal];
            }
            $taxRate = $this->numericString($data['tax_rate'] ?? null);
            $tax = bcadd(bcdiv(bcmul($subtotal, $taxRate, 4), '100', 4), '0.005', 2);
            $attributes = [...$data, 'currency' => 'PEN', 'subtotal' => $subtotal, 'tax' => $tax, 'total' => bcadd($subtotal, $tax, 2)];
            if ($order === null) {
                $order = PurchaseOrder::query()->create([...$attributes, 'created_by' => $user->id, 'status' => PurchaseOrderStatus::Draft]);
            } else {
                $order->update($attributes);
                $order->items()->delete();
            }
            $order->items()->createMany($preparedItems);
            foreach ($attachments as $attachment) {
                if ($attachment instanceof UploadedFile) {
                    $order->addMedia($attachment)->toMediaCollection('attachments');
                }
            }

            return $order;
        });
    }

    /** @return numeric-string */
    private function numericString(mixed $value): string
    {
        if (! is_numeric($value)) {
            throw new \LogicException('El pedido contiene una cantidad o costo invÃ¡lido.');
        }

        return (string) $value;
    }
}
