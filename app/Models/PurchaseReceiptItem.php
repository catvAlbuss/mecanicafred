<?php

namespace App\Models;

use Database\Factories\PurchaseReceiptItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purchase_receipt_id', 'purchase_order_item_id', 'product_id', 'quantity_received', 'unit_cost'])]
class PurchaseReceiptItem extends Model
{
    /** @use HasFactory<PurchaseReceiptItemFactory> */
    use HasFactory;

    /** @return BelongsTo<PurchaseReceipt, $this> */
    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    /** @return BelongsTo<PurchaseOrderItem, $this> */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Las lÃ­neas de recepciÃ³n son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Las lÃ­neas de recepciÃ³n son inmutables.'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity_received' => 'decimal:3', 'unit_cost' => 'decimal:4'];
    }
}
