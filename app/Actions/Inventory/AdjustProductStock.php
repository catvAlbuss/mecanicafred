<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustProductStock
{
    public function handle(Product $product, User $user, string $direction, string $quantity, string $reason): Product
    {
        if (! is_numeric($quantity)) {
            throw new \InvalidArgumentException('La cantidad debe ser numérica.');
        }

        return DB::transaction(function () use ($product, $user, $direction, $quantity, $reason): Product {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $stockBefore = (string) $lockedProduct->current_stock;
            $isEntry = $direction === 'in';
            $signedQuantity = $isEntry ? $quantity : bcsub('0', $quantity, 3);
            $stockAfter = bcadd($stockBefore, $signedQuantity, 3);

            if (bccomp($stockAfter, '0', 3) === -1) {
                throw ValidationException::withMessages([
                    'quantity' => 'La salida supera el stock disponible.',
                ]);
            }

            $lockedProduct->update(['current_stock' => $stockAfter]);
            $lockedProduct->inventoryMovements()->create([
                'user_id' => $user->id,
                'type' => $isEntry ? InventoryMovementType::AdjustmentIn : InventoryMovementType::AdjustmentOut,
                'quantity' => $signedQuantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $reason,
                'occurred_at' => now(),
            ]);

            return $lockedProduct;
        });
    }
}
