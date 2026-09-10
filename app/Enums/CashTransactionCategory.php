<?php

namespace App\Enums;

enum CashTransactionCategory: string
{
    case ProductSale = 'product_sale';
    case ExternalIncome = 'external_income';
    case CashDeposit = 'cash_deposit';
    case SupplyPurchase = 'supply_purchase';
    case SupplierPayment = 'supplier_payment';
    case OperatingExpense = 'operating_expense';
    case CashWithdrawal = 'cash_withdrawal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ProductSale => 'Venta de producto',
            self::ExternalIncome => 'Ingreso externo (servicio, propina, etc.)',
            self::CashDeposit => 'Ingreso de efectivo a caja',
            self::SupplyPurchase => 'Compra de insumo',
            self::SupplierPayment => 'Pago a proveedor',
            self::OperatingExpense => 'Gasto operativo (luz, agua, movilidad…)',
            self::CashWithdrawal => 'Retiro de efectivo de caja',
            self::Other => 'Otro',
        };
    }

    /**
     * Transaction types that a movement in this category may take.
     *
     * @return list<CashTransactionType>
     */
    public function allowedTypes(): array
    {
        return match ($this) {
            self::ProductSale, self::ExternalIncome, self::CashDeposit => [CashTransactionType::Income],
            self::Other => [CashTransactionType::Income, CashTransactionType::Expense],
            default => [CashTransactionType::Expense],
        };
    }

    /**
     * Categories a user may pick when registering a movement by hand.
     * Product sales are created only by the sales flow.
     */
    public function isManuallySelectable(): bool
    {
        return $this !== self::ProductSale;
    }
}
