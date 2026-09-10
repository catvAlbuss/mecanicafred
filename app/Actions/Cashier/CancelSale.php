<?php

namespace App\Actions\Cashier;

use App\Enums\CashTransactionCategory;
use App\Enums\CashTransactionType;
use App\Enums\InventoryMovementType;
use App\Enums\SaleStatus;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelSale
{
    /**
     * Void a completed sale: it returns the stock, writes the reversing
     * inventory movements and books a refund against the open register.
     */
    public function handle(Sale $sale, User $user, string $reason): Sale
    {
        return DB::transaction(function () use ($sale, $user, $reason): Sale {
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($locked->status !== SaleStatus::Completed) {
                throw ValidationException::withMessages(['sale' => 'Esta venta ya fue anulada.']);
            }

            $register = CashRegister::query()->open()->lockForUpdate()->first();

            if ($register === null) {
                throw ValidationException::withMessages(['sale' => 'Abre una caja para registrar la devolución del dinero.']);
            }

            $locked->load('items');
            $now = now();

            $productIds = $locked->items->pluck('product_id')->sort()->values()->all();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($locked->items as $item) {
                $product = $products->get($item->product_id);

                if (! $product instanceof Product) {
                    continue;
                }

                $stockBefore = (string) $product->current_stock;
                $stockAfter = bcadd($stockBefore, (string) $item->quantity, 3);
                $product->update(['current_stock' => $stockAfter]);

                $locked->inventoryMovements()->create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'type' => InventoryMovementType::SaleReturn,
                    'quantity' => (string) $item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reason' => "Anulación de venta {$locked->number}",
                    'occurred_at' => $now,
                ]);
            }

            $locked->cashTransactions()->create([
                'cash_register_id' => $register->id,
                'user_id' => $user->id,
                'type' => CashTransactionType::Expense,
                'category' => CashTransactionCategory::ProductSale,
                'payment_method' => $locked->payment_method,
                'amount' => $locked->total,
                'description' => "Devolución por anulación de venta {$locked->number}",
                'reference' => $locked->number,
                'occurred_at' => $now,
            ]);

            $locked->update([
                'status' => SaleStatus::Cancelled,
                'cancelled_by' => $user->id,
                'cancelled_at' => $now,
                'cancellation_reason' => $reason,
            ]);

            return $locked;
        });
    }
}
