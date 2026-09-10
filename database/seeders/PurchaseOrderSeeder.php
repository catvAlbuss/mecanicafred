<?php

namespace Database\Seeders;

use App\Actions\Purchases\SavePurchaseOrder;
use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Seed one confirmed purchase order so the barcode-scan receiving flow
     * has something to receive against out of the box.
     */
    public function run(SavePurchaseOrder $savePurchaseOrder): void
    {
        $supplier = Supplier::query()->where('tax_id', '20559876543')->first();
        $user = User::query()->where('email', 'admin@fredyracing.test')->first();

        if ($supplier === null || $user === null) {
            return;
        }

        if (PurchaseOrder::query()->where('supplier_id', $supplier->id)->exists()) {
            return;
        }

        $products = Product::query()->whereIn('sku', ['LUB-001', 'LUB-002', 'LUB-003'])->get();

        if ($products->count() < 3) {
            return;
        }

        $order = $savePurchaseOrder->handle([
            'supplier_id' => $supplier->id,
            'expected_at' => now()->addDays(3)->toDateString(),
            'tax_rate' => '18.00',
            'notes' => 'Pedido de demostración para probar la recepción por escaneo.',
            'items' => $products->map(fn (Product $product): array => [
                'product_id' => $product->id,
                'quantity_ordered' => '12.000',
                'unit_cost' => (string) ($product->last_purchase_cost ?? '20.0000'),
            ])->all(),
        ], $user);

        $order->update([
            'status' => PurchaseOrderStatus::Confirmed,
            'ordered_at' => now()->subDay(),
        ]);
    }
}
