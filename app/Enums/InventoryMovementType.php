<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case OpeningBalance = 'opening_balance';
    case Receipt = 'receipt';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case WorkshopConsumption = 'workshop_consumption';
    case SupplierReturn = 'supplier_return';

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => 'Saldo inicial',
            self::Receipt => 'Recepción',
            self::AdjustmentIn => 'Ajuste de entrada',
            self::AdjustmentOut => 'Ajuste de salida',
            self::WorkshopConsumption => 'Consumo de taller',
            self::SupplierReturn => 'Devolución a proveedor',
        };
    }
}
