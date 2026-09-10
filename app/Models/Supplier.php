<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'tax_id',
    'business_name',
    'trade_name',
    'contact_name',
    'phone',
    'secondary_phone',
    'email',
    'address',
    'district',
    'province',
    'notes',
    'is_active',
])]
class Supplier extends Model implements HasMedia
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Get the products offered by the supplier.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
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

    /** @return HasMany<SupplierInquiry, $this> */
    public function inquiries(): HasMany
    {
        return $this->hasMany(SupplierInquiry::class);
    }

    /** @return HasMany<PurchaseOrder, $this> */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Register the media collections available for a supplier.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->singleFile();

        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
