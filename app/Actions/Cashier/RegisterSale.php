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

class RegisterSale
{
    /**
     * Register a completed product sale: it discounts stock, writes the
     * inventory movements and the cash income in a single transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(CashRegister $register, User $user, array $data): Sale
    {
        return DB::transaction(function () use ($register, $user, $data): Sale {
            $idempotencyKey = (string) $data['idempotency_key'];
            $existing = Sale::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            $locked = CashRegister::query()->lockForUpdate()->findOrFail($register->id);

            if (! $locked->isOpen()) {
                throw ValidationException::withMessages(['items' => 'La caja está cerrada. Ábrela para vender.']);
            }

            $wanted = $this->aggregateLines($data['items'] ?? []);

            if ($wanted === []) {
                throw ValidationException::withMessages(['items' => 'Agrega al menos un producto con cantidad mayor que cero.']);
            }

            ksort($wanted);
            $products = Product::query()
                ->whereIn('id', array_keys($wanted))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($wanted)) {
                throw ValidationException::withMessages(['items' => 'Uno de los productos ya no existe.']);
            }

            $subtotal = '0.00';
            $prepared = [];

            foreach ($wanted as $productId => $line) {
                $product = $products->get($productId);

                if (! $product instanceof Product) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos ya no existe.']);
                }

                if (! $product->is_active) {
                    throw ValidationException::withMessages(['items' => "{$product->name} está inactivo y no puede venderse."]);
                }

                if (bccomp((string) $product->current_stock, $line['quantity'], 3) === -1) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente de {$product->name}: disponible {$product->current_stock}, se pidió {$line['quantity']}.",
                    ]);
                }

                $lineSubtotal = bcadd(bcmul($line['quantity'], $line['unit_price'], 4), '0.005', 2);
                $subtotal = bcadd($subtotal, $lineSubtotal, 2);
                $prepared[$productId] = [...$line, 'subtotal' => $lineSubtotal, 'product' => $product];
            }

            $discount = $this->numericString($data['discount'] ?? '0');

            if (bccomp($discount, '0', 2) === -1) {
                $discount = '0.00';
            }

            if (bccomp($discount, $subtotal, 2) === 1) {
                throw ValidationException::withMessages(['discount' => 'El descuento no puede superar el subtotal.']);
            }

            $total = bcsub($subtotal, $discount, 2);
            $soldAt = now();

            $sale = Sale::query()->create([
                'idempotency_key' => $idempotencyKey,
                'status' => SaleStatus::Completed,
                'cash_register_id' => $locked->id,
                'sold_by' => $user->id,
                'payment_method' => $data['payment_method'],
                'customer_name' => $data['customer_name'] ?? null,
                'customer_document' => $data['customer_document'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'sold_at' => $soldAt,
            ]);

            foreach ($prepared as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $stockBefore = (string) $product->current_stock;
                $stockAfter = bcsub($stockBefore, $line['quantity'], 3);

                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit' => $product->unit->value,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'unit_cost' => (string) ($product->last_purchase_cost ?? '0'),
                    'subtotal' => $line['subtotal'],
                ]);

                $product->update(['current_stock' => $stockAfter]);

                $sale->inventoryMovements()->create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'type' => InventoryMovementType::Sale,
                    'quantity' => bcsub('0', $line['quantity'], 3),
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reason' => "Venta {$sale->number}",
                    'occurred_at' => $soldAt,
                ]);
            }

            $sale->cashTransactions()->create([
                'cash_register_id' => $locked->id,
                'user_id' => $user->id,
                'type' => CashTransactionType::Income,
                'category' => CashTransactionCategory::ProductSale,
                'payment_method' => $data['payment_method'],
                'amount' => $total,
                'description' => "Venta {$sale->number}",
                'reference' => $sale->number,
                'occurred_at' => $soldAt,
            ]);

            return $sale;
        });
    }

    /**
     * Collapse repeated scans of the same product into one line, keeping the
     * most recent unit price entered.
     *
     * @return array<int, array{quantity: numeric-string, unit_price: numeric-string}>
     */
    private function aggregateLines(mixed $items): array
    {
        if (! is_array($items)) {
            throw new \LogicException('Las líneas de la venta no tienen el formato esperado.');
        }

        $wanted = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['product_id'])) {
                throw new \LogicException('Una línea de la venta no tiene el formato esperado.');
            }

            $quantity = $this->numericString($item['quantity'] ?? null);
            $unitPrice = $this->numericString($item['unit_price'] ?? null);

            if (bccomp($quantity, '0', 3) !== 1) {
                continue;
            }

            $productId = (int) $item['product_id'];

            if (isset($wanted[$productId])) {
                $wanted[$productId]['quantity'] = bcadd($wanted[$productId]['quantity'], $quantity, 3);
                $wanted[$productId]['unit_price'] = $unitPrice;
            } else {
                $wanted[$productId] = ['quantity' => $quantity, 'unit_price' => $unitPrice];
            }
        }

        return $wanted;
    }

    /** @return numeric-string */
    private function numericString(mixed $value): string
    {
        if (! is_numeric($value)) {
            throw new \LogicException('La venta contiene una cantidad o precio inválido.');
        }

        return (string) $value;
    }
}
