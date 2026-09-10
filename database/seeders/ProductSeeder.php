<?php

namespace Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Enums\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed five products for each demonstration category.
     *
     * Every product is created without images and receives an automatic
     * scannable EAN-13 barcode through the Product model's created hook.
     */
    public function run(): void
    {
        foreach ($this->catalog() as $slug => $products) {
            $category = ProductCategory::query()->where('slug', $slug)->first();

            if ($category === null) {
                continue;
            }

            foreach ($products as $product) {
                $created = Product::query()->firstOrCreate(
                    ['sku' => $product['sku']],
                    [
                        'product_category_id' => $category->id,
                        'name' => $product['name'],
                        'brand' => $product['brand'],
                        'unit' => $product['unit'],
                        'minimum_stock' => $product['minimum_stock'],
                        'current_stock' => $product['current_stock'],
                        'location' => $product['location'],
                        'last_purchase_cost' => $product['last_purchase_cost'],
                        'is_active' => true,
                    ],
                );

                if ($created->barcode === null) {
                    $created->forceFill(['barcode' => Product::generateBarcode($created->id)])->saveQuietly();
                }

                $this->recordOpeningBalance($created);
            }
        }
    }

    private function recordOpeningBalance(Product $product): void
    {
        if (bccomp((string) $product->current_stock, '0', 3) !== 1) {
            return;
        }

        $product->inventoryMovements()->firstOrCreate(
            ['type' => InventoryMovementType::OpeningBalance, 'reason' => 'Saldo inicial de demostración'],
            [
                'user_id' => null,
                'quantity' => $product->current_stock,
                'stock_before' => '0',
                'stock_after' => $product->current_stock,
                'occurred_at' => $product->created_at,
            ],
        );
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function catalog(): array
    {
        return [
            'lubricantes' => [
                ['sku' => 'LUB-001', 'name' => 'Aceite motor 10W-30 mineral', 'brand' => 'Vistony', 'unit' => MeasurementUnit::Liter, 'minimum_stock' => 20, 'current_stock' => 48, 'location' => 'L-A01', 'last_purchase_cost' => 22.5000],
                ['sku' => 'LUB-002', 'name' => 'Aceite motor 20W-50 mineral', 'brand' => 'Vistony', 'unit' => MeasurementUnit::Liter, 'minimum_stock' => 20, 'current_stock' => 12, 'location' => 'L-A02', 'last_purchase_cost' => 21.0000],
                ['sku' => 'LUB-003', 'name' => 'Aceite sintético 5W-40', 'brand' => 'Mobil', 'unit' => MeasurementUnit::Liter, 'minimum_stock' => 15, 'current_stock' => 30, 'location' => 'L-A03', 'last_purchase_cost' => 41.9000],
                ['sku' => 'LUB-004', 'name' => 'Grasa multipropósito EP-2', 'brand' => 'Shell', 'unit' => MeasurementUnit::Kilogram, 'minimum_stock' => 5, 'current_stock' => 0, 'location' => 'L-B01', 'last_purchase_cost' => 18.4000],
                ['sku' => 'LUB-005', 'name' => 'Aceite de transmisión 80W-90', 'brand' => 'Castrol', 'unit' => MeasurementUnit::Liter, 'minimum_stock' => 10, 'current_stock' => 16, 'location' => 'L-A04', 'last_purchase_cost' => 27.8000],
            ],
            'repuestos' => [
                ['sku' => 'REP-001', 'name' => 'Filtro de aceite roscado', 'brand' => 'Bosch', 'unit' => MeasurementUnit::Unit, 'minimum_stock' => 12, 'current_stock' => 40, 'location' => 'R-A01', 'last_purchase_cost' => 12.5000],
                ['sku' => 'REP-002', 'name' => 'Filtro de aire panel', 'brand' => 'Sakura', 'unit' => MeasurementUnit::Unit, 'minimum_stock' => 10, 'current_stock' => 7, 'location' => 'R-A02', 'last_purchase_cost' => 19.9000],
                ['sku' => 'REP-003', 'name' => 'Bujía de encendido NGK', 'brand' => 'NGK', 'unit' => MeasurementUnit::Unit, 'minimum_stock' => 20, 'current_stock' => 64, 'location' => 'R-A03', 'last_purchase_cost' => 9.8000],
                ['sku' => 'REP-004', 'name' => 'Pastillas de freno delanteras', 'brand' => 'Brembo', 'unit' => MeasurementUnit::Set, 'minimum_stock' => 8, 'current_stock' => 5, 'location' => 'R-B01', 'last_purchase_cost' => 58.0000],
                ['sku' => 'REP-005', 'name' => 'Kit de cadena y piñones', 'brand' => 'DID', 'unit' => MeasurementUnit::Set, 'minimum_stock' => 4, 'current_stock' => 0, 'location' => 'R-B02', 'last_purchase_cost' => 145.0000],
            ],
            'herramientas' => [
                ['sku' => 'HER-001', 'name' => 'Juego de llaves combinadas 8-19 mm', 'brand' => 'Stanley', 'unit' => MeasurementUnit::Set, 'minimum_stock' => 2, 'current_stock' => 4, 'location' => 'H-A01', 'last_purchase_cost' => 129.9000],
                ['sku' => 'HER-002', 'name' => 'Torquímetro 1/2" 28-210 Nm', 'brand' => 'Truper', 'unit' => MeasurementUnit::Unit, 'minimum_stock' => 1, 'current_stock' => 2, 'location' => 'H-A02', 'last_purchase_cost' => 210.0000],
                ['sku' => 'HER-003', 'name' => 'Destornillador de impacto', 'brand' => 'Bosch', 'unit' => MeasurementUnit::Unit, 'minimum_stock' => 1, 'current_stock' => 1, 'location' => 'H-A03', 'last_purchase_cost' => 89.5000],
                ['sku' => 'HER-004', 'name' => 'Alicate de presión 10"', 'brand' => 'Irwin', 'unit' => MeasurementUnit::Unit, 'minimum_stock' => 2, 'current_stock' => 3, 'location' => 'H-A04', 'last_purchase_cost' => 34.9000],
                ['sku' => 'HER-005', 'name' => 'Dado hexagonal 1/2" 17 mm', 'brand' => 'Stanley', 'unit' => MeasurementUnit::Unit, 'minimum_stock' => 4, 'current_stock' => 2, 'location' => 'H-B01', 'last_purchase_cost' => 11.2000],
            ],
            'consumibles' => [
                ['sku' => 'CON-001', 'name' => 'Trapo industrial (kg)', 'brand' => null, 'unit' => MeasurementUnit::Kilogram, 'minimum_stock' => 5, 'current_stock' => 18, 'location' => 'C-A01', 'last_purchase_cost' => 6.5000],
                ['sku' => 'CON-002', 'name' => 'Limpiador de frenos en aerosol', 'brand' => 'Abro', 'unit' => MeasurementUnit::Unit, 'minimum_stock' => 12, 'current_stock' => 9, 'location' => 'C-A02', 'last_purchase_cost' => 14.9000],
                ['sku' => 'CON-003', 'name' => 'Guantes de nitrilo (caja 100)', 'brand' => null, 'unit' => MeasurementUnit::Box, 'minimum_stock' => 4, 'current_stock' => 10, 'location' => 'C-A03', 'last_purchase_cost' => 32.0000],
                ['sku' => 'CON-004', 'name' => 'Silicona instantánea gris', 'brand' => 'Permatex', 'unit' => MeasurementUnit::Unit, 'minimum_stock' => 6, 'current_stock' => 0, 'location' => 'C-A04', 'last_purchase_cost' => 21.5000],
                ['sku' => 'CON-005', 'name' => 'Precinto plástico 300 mm (bolsa 100)', 'brand' => null, 'unit' => MeasurementUnit::Box, 'minimum_stock' => 3, 'current_stock' => 6, 'location' => 'C-A05', 'last_purchase_cost' => 8.9000],
            ],
        ];
    }
}
