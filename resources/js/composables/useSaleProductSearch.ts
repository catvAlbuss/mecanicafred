import { search } from "@/routes/sales/products";
import type { SaleProduct } from "@/types";

/**
 * Text search for the point-of-sale screen, returning active products with
 * their sale price and current stock.
 */
export function useSaleProductSearch() {
    async function findProducts(term: string): Promise<SaleProduct[]> {
        const response = await fetch(search.url({ query: { q: term } }), {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        });

        if (!response.ok) {
            throw new Error("No se pudo buscar productos. Intenta de nuevo.");
        }

        const data = (await response.json()) as { products: SaleProduct[] };

        return data.products;
    }

    return { findProducts };
}
