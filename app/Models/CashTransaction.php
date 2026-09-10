<?php

namespace App\Models;

use App\Enums\CashTransactionCategory;
use App\Enums\CashTransactionType;
use App\Enums\PaymentMethod;
use Database\Factories\CashTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property CashTransactionType $type
 * @property CashTransactionCategory $category
 * @property PaymentMethod $payment_method
 * @property Carbon $occurred_at
 */
#[Fillable([
    'cash_register_id',
    'user_id',
    'type',
    'category',
    'payment_method',
    'amount',
    'description',
    'reference',
    'occurred_at',
])]
class CashTransaction extends Model
{
    /** @use HasFactory<CashTransactionFactory> */
    use HasFactory;

    /** @return BelongsTo<CashRegister, $this> */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Signed amount for balance math: positive for income, negative for expense.
     */
    public function signedAmount(): string
    {
        return $this->type === CashTransactionType::Expense
            ? bcsub('0', (string) $this->amount, 2)
            : (string) $this->amount;
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Los movimientos de caja son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Los movimientos de caja son inmutables.'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => CashTransactionType::class,
            'category' => CashTransactionCategory::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }
}
