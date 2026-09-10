<?php

namespace App\Enums;

enum CashTransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso',
            self::Expense => 'Egreso',
        };
    }

    /**
     * Sign applied to the amount when accumulating a cash balance.
     */
    public function sign(): int
    {
        return match ($this) {
            self::Income => 1,
            self::Expense => -1,
        };
    }
}
