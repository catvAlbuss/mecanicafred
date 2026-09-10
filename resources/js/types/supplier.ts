export type SupplierSummary = {
    id: number;
    tax_id: string;
    business_name: string;
    trade_name: string | null;
    contact_name: string | null;
    phone: string | null;
    email: string | null;
    is_active: boolean;
    products_count: number;
    logo_url: string | null;
};

export type SupplierFormData = {
    id: number;
    tax_id: string;
    business_name: string;
    trade_name: string | null;
    contact_name: string | null;
    phone: string | null;
    secondary_phone: string | null;
    email: string | null;
    address: string | null;
    district: string | null;
    province: string | null;
    notes: string | null;
    is_active: boolean;
};

export type SupplierAttachment = {
    id: number;
    name: string;
    file_name: string;
    mime_type: string;
    size: number;
    url: string;
};

export type SupplierProduct = {
    id: number;
    sku: string;
    name: string;
    category: string;
    availability_status: string | null;
};

export type SupplierDetail = SupplierFormData & {
    logo_url: string | null;
    logo_media_id: number | null;
    attachments: SupplierAttachment[];
    products?: SupplierProduct[];
};

export type SupplierPaginator = {
    data: SupplierSummary[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
