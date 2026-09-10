<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property InventoryMovementType $type
 * @property Carbon $occurred_at
 * @property User $user
 */
#[Fillable(['product_id', 'user_id', 'type', 'quantity', 'stock_before', 'stock_after', 'reason', 'occurred_at'])]
class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Los movimientos de inventario son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Los movimientos de inventario son inmutables.'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => InventoryMovementType::class,
            'quantity' => 'decimal:3',
            'stock_before' => 'decimal:3',
            'stock_after' => 'decimal:3',
            'occurred_at' => 'datetime',
        ];
    }
}
