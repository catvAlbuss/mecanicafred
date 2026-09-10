<?php

namespace App\Enums;

enum CashTransactionCategory: string
{
    case ProductSale = 'product_sale';
    case SupplyPurchase = 'supply_purchase';
    case SupplierPayment = 'supplier_payment';
    case OperatingExpense = 'operating_expense';
    case CashWithdrawal = 'cash_withdrawal';
    case CashDeposit = 'cash_deposit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ProductSale => 'Venta de producto',
            self::SupplyPurchase => 'Compra de insumo',
            self::SupplierPayment => 'Pago a proveedor',
            self::OperatingExpense => 'Gasto operativo',
            self::CashWithdrawal => 'Retiro de efectivo',
            self::CashDeposit => 'Ingreso de efectivo',
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
            self::ProductSale, self::CashDeposit => [CashTransactionType::Income],
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
