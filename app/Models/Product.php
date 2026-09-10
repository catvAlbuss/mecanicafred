<?php

namespace App\Models;

use App\Enums\MeasurementUnit;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property MeasurementUnit $unit
 * @property ProductCategory $category
 */
#[Fillable([
    'product_category_id',
    'sku',
    'barcode',
    'name',
    'description',
    'brand',
    'unit',
    'minimum_stock',
    'current_stock',
    'location',
    'last_purchase_cost',
    'is_active',
])]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Get the category that classifies the product.
     *
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Get the suppliers that offer the product.
     *
     * @return BelongsToMany<Supplier, $this>
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot([
                'supplier_sku',
                'last_unit_cost',
                'lead_time_days',
                'availability_status',
                'available_quantity',
                'last_checked_at',
                'is_preferred',
            ])
            ->withTimestamps();
    }

    /** @return HasMany<InventoryMovement, $this> */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /** @return HasMany<SupplierInquiryItem, $this> */
    public function inquiryItems(): HasMany
    {
        return $this->hasMany(SupplierInquiryItem::class);
    }

    /** @return HasMany<PurchaseOrderItem, $this> */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $product): void {
            if ($product->barcode === null) {
                $product->updateQuietly(['barcode' => self::generateBarcode($product->id)]);
            }
        });
    }

    /**
     * Build a scannable EAN-13 barcode from the product id using the "200"
     * internal-use prefix and an appended modulo-10 check digit.
     */
    public static function generateBarcode(int $id): string
    {
        $base = sprintf('200%09d', $id);

        $sum = 0;

        foreach (str_split($base) as $position => $digit) {
            $sum += (int) $digit * ($position % 2 === 0 ? 1 : 3);
        }

        return $base.((10 - $sum % 10) % 10);
    }

    /**
     * Register the media collections available for a product.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => MeasurementUnit::class,
            'minimum_stock' => 'decimal:3',
            'current_stock' => 'decimal:3',
            'last_purchase_cost' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }
}
