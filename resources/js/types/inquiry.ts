import type { Paginator } from './product';
export type InquiryProduct = {
    id: number;
    sku: string;
    name: string;
    unit: string;
};
export type InquirySupplier = {
    id: number;
    name: string;
    products: InquiryProduct[];
};
export type InquiryItem = {
    id?: number;
    product_id: number | string;
    product_name?: string;
    product_sku?: string;
    unit_label?: string;
    quantity_requested: string;
    is_available?: boolean | null;
    quantity_available?: string | null;
    quoted_unit_cost?: string | null;
    supplier_notes?: string | null;
};
export type InquirySummary = {
    id: number;
    number: string;
    supplier_name: string;
    status: string;
    status_label: string;
    items_count: number;
    requested_at: string | null;
    created_at: string;
};
export type InquiryDetail = {
    id: number;
    number: string;
    supplier_id: number;
    status: string;
    status_label: string;
    supplier_name: string;
    requester_name: string;
    requested_at: string | null;
    responded_at: string | null;
    valid_until: string | null;
    notes: string | null;
    items: InquiryItem[];
    attachments: {
        id: number;
        name: string;
        file_name: string;
        url: string;
        size: number;
    }[];
    purchase_order: { id: number; number: string } | null;
};
export type InquiryPaginator = Paginator<InquirySummary>;
