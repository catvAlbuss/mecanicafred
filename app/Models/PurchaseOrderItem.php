<?php

namespace App\Models;

use App\Enums\MeasurementUnit;
use Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property MeasurementUnit $unit */
#[Fillable(['purchase_order_id', 'product_id', 'product_name', 'product_sku', 'unit', 'quantity_ordered', 'quantity_received', 'unit_cost', 'subtotal'])]
class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory;

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<PurchaseReceiptItem, $this> */
    public function receiptItems(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['unit' => MeasurementUnit::class, 'quantity_ordered' => 'decimal:3', 'quantity_received' => 'decimal:3', 'unit_cost' => 'decimal:4', 'subtotal' => 'decimal:2'];
    }
}
