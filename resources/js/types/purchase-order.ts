import type { Paginator } from './product';

export type OrderProduct = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    last_unit_cost: string | null;
};
export type OrderSupplier = {
    id: number;
    name: string;
    products: OrderProduct[];
};
export type OrderItem = {
    id?: number;
    product_id: number | string;
    product_name?: string;
    product_sku?: string;
    unit_label?: string;
    quantity_ordered: string;
    quantity_received?: string;
    unit_cost: string;
    subtotal?: string;
};
export type OrderSummary = {
    id: number;
    number: string;
    supplier_name: string;
    status: string;
    status_label: string;
    items_count: number;
    total: string;
    expected_at: string | null;
    created_at: string;
};
export type Attachment = {
    id: number;
    name: string;
    file_name: string;
    url: string;
    size: number;
};
export type ReceiptItem = {
    id: number;
    product_name: string;
    quantity_received: string;
    unit_cost: string;
    ordered_unit_cost: string;
    unit_label: string;
};
export type PurchaseReceipt = {
    id: number;
    number: string;
    received_at: string;
    receiver_name: string;
    supplier_document_number: string | null;
    notes: string | null;
    items: ReceiptItem[];
    attachments: Attachment[];
};
export type OrderDetail = {
    id: number;
    number: string;
    supplier_id: number;
    supplier_name: string;
    status: string;
    status_label: string;
    currency: string;
    expected_at: string | null;
    ordered_at: string | null;
    subtotal: string;
    tax_rate: string;
    tax: string;
    total: string;
    notes: string | null;
    cancellation_reason: string | null;
    creator_name: string;
    approver_name: string | null;
    created_at: string;
    inquiry: { id: number; number: string } | null;
    items: OrderItem[];
    attachments: Attachment[];
    receipts: PurchaseReceipt[];
};
export type OrderPaginator = Paginator<OrderSummary>;

export type HistoryOrder = OrderSummary & {
    receipts_count: number;
    closed_at: string;
};
export type HistoryPaginator = Paginator<HistoryOrder>;
