<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Yape = 'yape';
    case Plin = 'plin';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Yape => 'Yape',
            self::Plin => 'Plin',
            self::BankTransfer => 'Transferencia',
            self::Card => 'Tarjeta',
        };
    }

    /**
     * Whether this method moves physical cash in the drawer, which is what
     * the register count at closing time reconciles.
     */
    public function affectsCashDrawer(): bool
    {
        return $this === self::Cash;
    }
}
