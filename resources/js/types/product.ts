export type Option = { value: string; label: string };
export type CategoryOption = { id: number; name: string; type: string };
export type ProductImage = {
    id: number;
    name: string;
    url: string;
    is_primary: boolean;
};
export type ProductCategory = CategoryOption & { type_label: string };
export type ProductSummary = {
    id: number;
    product_category_id: number;
    sku: string;
    barcode: string | null;
    name: string;
    description: string | null;
    brand: string | null;
    unit: string;
    unit_label: string;
    minimum_stock: string;
    current_stock: string;
    location: string | null;
    last_purchase_cost: string | null;
    sale_price: string | null;
    is_active: boolean;
    category: ProductCategory;
    stock_status: 'available' | 'low' | 'out';
    primary_image_url: string | null;
};
export type ProductDetail = Omit<
    ProductSummary,
    'stock_status' | 'primary_image_url'
> & {
    images: ProductImage[];
    suppliers: {
        id: number;
        name: string;
        is_active: boolean;
        availability_status: string;
        available_quantity: string | null;
        last_unit_cost: string | null;
        lead_time_days: number | null;
    }[];
};
export type ScannedProduct = {
    id: number;
    sku: string;
    barcode: string | null;
    name: string;
    brand: string | null;
    unit: string;
    unit_label: string;
    current_stock: string;
    last_purchase_cost: string | null;
    sale_price: string | null;
    is_active: boolean;
    category_name: string;
};
export type InventoryMovement = {
    id: number;
    type: string;
    type_label: string;
    quantity: string;
    stock_before: string;
    stock_after: string;
    reason: string | null;
    occurred_at: string;
    user_name: string;
};
export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
