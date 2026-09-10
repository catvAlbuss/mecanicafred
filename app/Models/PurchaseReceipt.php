<?php

namespace App\Models;

use Database\Factories\PurchaseReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** @property Carbon $received_at */
#[Fillable(['number', 'idempotency_key', 'purchase_order_id', 'received_by', 'received_at', 'supplier_document_number', 'notes'])]
class PurchaseReceipt extends Model implements HasMedia
{
    /** @use HasFactory<PurchaseReceiptFactory> */
    use HasFactory, InteractsWithMedia;

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return BelongsTo<User, $this> */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /** @return HasMany<PurchaseReceiptItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class);
    }

    /** @return MorphMany<InventoryMovement, $this> */
    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
    }

    protected static function booted(): void
    {
        static::created(function (self $receipt): void {
            if ($receipt->number === null) {
                $receipt->updateQuietly(['number' => sprintf('REC-%s-%06d', $receipt->created_at->format('Y'), $receipt->id)]);
            }
        });
        static::updating(fn () => throw new \LogicException('Las recepciones son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Las recepciones son inmutables.'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }
}
