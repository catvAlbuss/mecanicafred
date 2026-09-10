<?php

namespace App\Models;

use App\Enums\SupplierInquiryStatus;
use Database\Factories\SupplierInquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property SupplierInquiryStatus $status
 * @property Carbon|null $requested_at
 * @property Carbon|null $responded_at
 * @property Carbon|null $valid_until
 * @property Supplier $supplier
 * @property User $requester
 * @property PurchaseOrder|null $purchaseOrder
 */
#[Fillable(['number', 'supplier_id', 'requested_by', 'status', 'requested_at', 'responded_at', 'valid_until', 'notes'])]
class SupplierInquiry extends Model implements HasMedia
{
    /** @use HasFactory<SupplierInquiryFactory> */
    use HasFactory, InteractsWithMedia;

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return HasMany<SupplierInquiryItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SupplierInquiryItem::class);
    }

    /** @return HasOne<PurchaseOrder, $this> */
    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
    }

    protected static function booted(): void
    {
        static::created(function (self $inquiry): void {
            if ($inquiry->number === null) {
                $inquiry->updateQuietly(['number' => sprintf('COT-%s-%06d', $inquiry->created_at->format('Y'), $inquiry->id)]);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => SupplierInquiryStatus::class, 'requested_at' => 'datetime', 'responded_at' => 'datetime', 'valid_until' => 'date'];
    }
}
