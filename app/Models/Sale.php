<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property SaleStatus $status
 * @property PaymentMethod $payment_method
 * @property Carbon $sold_at
 * @property Carbon|null $cancelled_at
 */
#[Fillable([
    'number',
    'idempotency_key',
    'status',
    'cash_register_id',
    'sold_by',
    'payment_method',
    'customer_name',
    'customer_document',
    'subtotal',
    'discount',
    'total',
    'notes',
    'cancelled_by',
    'cancelled_at',
    'cancellation_reason',
    'sold_at',
])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /** @return BelongsTo<CashRegister, $this> */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /** @return BelongsTo<User, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    /** @return BelongsTo<User, $this> */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return MorphMany<InventoryMovement, $this> */
    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    /** @return MorphMany<CashTransaction, $this> */
    public function cashTransactions(): MorphMany
    {
        return $this->morphMany(CashTransaction::class, 'source');
    }

    public function isCancelled(): bool
    {
        return $this->status === SaleStatus::Cancelled;
    }

    protected static function booted(): void
    {
        static::created(function (self $sale): void {
            if ($sale->number === null) {
                $sale->updateQuietly(['number' => sprintf('VTA-%s-%06d', $sale->sold_at->format('Y'), $sale->id)]);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'sold_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
