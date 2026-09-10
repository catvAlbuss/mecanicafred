import { lookup } from '@/routes/inventory/products/barcode';
import type { ScannedProduct } from '@/types';

/**
 * Look up a product by a scanned barcode (or SKU) through the JSON endpoint.
 * Rejects with a human-readable message when nothing matches.
 */
export function useProductLookup() {
    async function findByCode(code: string): Promise<ScannedProduct> {
        const response = await fetch(lookup.url({ query: { code } }), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (response.status === 404) {
            throw new Error(`Sin producto para el código ${code}.`);
        }

        if (!response.ok) {
            throw new Error(
                'No se pudo consultar el código. Intenta de nuevo.',
            );
        }

        return (await response.json()) as ScannedProduct;
    }

    return { findByCode };
}
