<?php

namespace App\Models;

use Database\Factories\SupplierInquiryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supplier_inquiry_id', 'product_id', 'quantity_requested', 'is_available', 'quantity_available', 'quoted_unit_cost', 'supplier_notes'])]
class SupplierInquiryItem extends Model
{
    /** @use HasFactory<SupplierInquiryItemFactory> */
    use HasFactory;

    /** @return BelongsTo<SupplierInquiry, $this> */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(SupplierInquiry::class, 'supplier_inquiry_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity_requested' => 'decimal:3', 'is_available' => 'boolean', 'quantity_available' => 'decimal:3', 'quoted_unit_cost' => 'decimal:4'];
    }
}
