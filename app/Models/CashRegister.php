<?php

namespace App\Models;

use App\Enums\CashRegisterStatus;
use App\Enums\CashTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Database\Factories\CashRegisterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property CashRegisterStatus $status
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 */
#[Fillable([
    'number',
    'status',
    'opened_by',
    'closed_by',
    'opening_amount',
    'expected_cash_amount',
    'counted_cash_amount',
    'difference',
    'opened_at',
    'closed_at',
    'opening_notes',
    'closing_notes',
])]
class CashRegister extends Model
{
    /** @use HasFactory<CashRegisterFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /** @return BelongsTo<User, $this> */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return HasMany<CashTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the single register currently open, if any.
     */
    public static function currentOpen(): ?self
    {
        return self::query()->where('status', CashRegisterStatus::Open)->first();
    }

    /** @param Builder<CashRegister> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', CashRegisterStatus::Open);
    }

    public function isOpen(): bool
    {
        return $this->status === CashRegisterStatus::Open;
    }

    /**
     * Totals for this register: income/expense, net, expected cash in the
     * drawer and a breakdown per payment method.
     *
     * @return array{income: numeric-string, expense: numeric-string, net: numeric-string, expected_cash: numeric-string, sales_count: int, transactions_count: int, by_method: array<string, array{label: string, in: numeric-string, out: numeric-string}>}
     */
    public function summary(): array
    {
        $income = '0.00';
        $expense = '0.00';
        $byMethod = [];

        foreach (PaymentMethod::cases() as $method) {
            $byMethod[$method->value] = ['label' => $method->label(), 'in' => '0.00', 'out' => '0.00'];
        }

        $transactions = $this->transactions()->get(['type', 'payment_method', 'amount']);

        foreach ($transactions as $transaction) {
            $amount = (string) $transaction->amount;
            $bucket = $transaction->type === CashTransactionType::Income ? 'in' : 'out';
            $byMethod[$transaction->payment_method->value][$bucket] = bcadd(
                $byMethod[$transaction->payment_method->value][$bucket],
                $amount,
                2,
            );

            if ($transaction->type === CashTransactionType::Income) {
                $income = bcadd($income, $amount, 2);
            } else {
                $expense = bcadd($expense, $amount, 2);
            }
        }

        $cash = $byMethod[PaymentMethod::Cash->value];
        $expectedCash = bcsub(bcadd((string) $this->opening_amount, $cash['in'], 2), $cash['out'], 2);

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => bcsub($income, $expense, 2),
            'expected_cash' => $expectedCash,
            'sales_count' => $this->sales()->where('status', SaleStatus::Completed)->count(),
            'transactions_count' => $transactions->count(),
            'by_method' => $byMethod,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $register): void {
            if ($register->number === null) {
                $register->updateQuietly(['number' => sprintf('CAJA-%s-%06d', $register->opened_at->format('Y'), $register->id)]);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CashRegisterStatus::class,
            'opening_amount' => 'decimal:2',
            'expected_cash_amount' => 'decimal:2',
            'counted_cash_amount' => 'decimal:2',
            'difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
