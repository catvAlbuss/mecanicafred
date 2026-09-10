<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property PurchaseOrderStatus $status
 * @property Carbon|null $ordered_at
 * @property Carbon|null $expected_at
 * @property Carbon|null $received_at
 * @property Carbon $created_at
 */
#[Fillable(['number', 'supplier_id', 'supplier_inquiry_id', 'created_by', 'approved_by', 'status', 'currency', 'ordered_at', 'expected_at', 'received_at', 'subtotal', 'tax_rate', 'tax', 'total', 'notes', 'cancellation_reason'])]
class PurchaseOrder extends Model implements HasMedia
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<SupplierInquiry, $this> */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(SupplierInquiry::class, 'supplier_inquiry_id');
    }

    /** @return HasMany<PurchaseOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** @return HasMany<PurchaseReceipt, $this> */
    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static function booted(): void
    {
        static::created(function (self $order): void {
            if ($order->number === null) {
                $order->updateQuietly(['number' => sprintf('OC-%s-%06d', $order->created_at->format('Y'), $order->id)]);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => PurchaseOrderStatus::class, 'ordered_at' => 'datetime', 'expected_at' => 'date', 'received_at' => 'datetime', 'subtotal' => 'decimal:2', 'tax_rate' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2'];
    }
}
