import type { Paginator } from "./product";

export type CashOption = { value: string; label: string };
export type ExpenseCategoryOption = CashOption & { types: string[] };

export type CashByMethod = Record<
    string,
    { label: string; in: string; out: string }
>;

export type CashSummary = {
    income: string;
    expense: string;
    net: string;
    expected_cash: string;
    sales_count: number;
    transactions_count: number;
    by_method: CashByMethod;
};

export type CashTransactionRow = {
    id: number;
    type: string;
    type_label: string;
    category: string;
    category_label: string;
    payment_method: string;
    payment_method_label: string;
    amount: string;
    description: string;
    reference: string | null;
    user_name: string;
    occurred_at: string;
};

export type CashRegisterData = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    opened_by: string;
    closed_by: string | null;
    opening_amount: string;
    expected_cash_amount: string | null;
    counted_cash_amount: string | null;
    difference: string | null;
    opened_at: string;
    closed_at: string | null;
    opening_notes: string | null;
    closing_notes: string | null;
};

export type OpenCashRegister = CashRegisterData & {
    summary: CashSummary;
    transactions: CashTransactionRow[];
};

export type CashRegisterHistoryRow = {
    id: number;
    number: string;
    opened_by: string;
    closed_by: string | null;
    opening_amount: string;
    expected_cash_amount: string | null;
    counted_cash_amount: string | null;
    difference: string | null;
    opened_at: string;
    closed_at: string | null;
    closing_notes: string | null;
};

export type CashRegisterHistoryPaginator = Paginator<CashRegisterHistoryRow>;

export type SaleProduct = {
    id: number;
    sku: string;
    barcode: string | null;
    name: string;
    brand: string | null;
    unit_label: string;
    current_stock: string;
    sale_price: string | null;
};

export type SaleSummary = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    payment_method_label: string;
    customer_name: string | null;
    items_count: number;
    total: string;
    seller_name: string;
    sold_at: string;
};

export type SalePaginator = Paginator<SaleSummary>;

export type SaleItemRow = {
    id: number;
    product_name: string;
    product_sku: string;
    quantity: string;
    unit_price: string;
    subtotal: string;
};

export type SaleDetail = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    register_number: string;
    seller_name: string;
    canceller_name: string | null;
    payment_method: string;
    payment_method_label: string;
    customer_name: string | null;
    customer_document: string | null;
    subtotal: string;
    discount: string;
    total: string;
    notes: string | null;
    cancellation_reason: string | null;
    sold_at: string;
    cancelled_at: string | null;
    items: SaleItemRow[];
};
