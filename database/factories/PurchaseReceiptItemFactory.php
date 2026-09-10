<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReceiptItem>
 */
class PurchaseReceiptItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_receipt_id' => PurchaseReceipt::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
            'product_id' => Product::factory(),
            'quantity_received' => '1.000',
            'unit_cost' => '10.0000',
        ];
    }
}
