<?php

namespace Database\Seeders;

use App\Enums\SupplierAvailabilityStatus;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Seed demonstration suppliers and link them to part of the catalog.
     */
    public function run(): void
    {
        $autoParts = Supplier::query()->firstOrCreate(
            ['tax_id' => '20601234567'],
            [
                'business_name' => 'AutoParts Racing S.A.C.',
                'trade_name' => 'AutoParts Racing',
                'contact_name' => 'María López',
                'phone' => '987654321',
                'email' => 'ventas@autoparts-racing.test',
                'address' => 'Av. Los Repuestos 125',
                'district' => 'La Victoria',
                'province' => 'Lima',
                'is_active' => true,
            ],
        );

        $lubricentro = Supplier::query()->firstOrCreate(
            ['tax_id' => '20559876543'],
            [
                'business_name' => 'Lubricentro El Motor E.I.R.L.',
                'trade_name' => 'Lubricentro El Motor',
                'contact_name' => 'Jorge Ramírez',
                'phone' => '956112233',
                'email' => 'pedidos@elmotor.test',
                'address' => 'Jr. Aceites 480',
                'district' => 'Ate',
                'province' => 'Lima',
                'is_active' => true,
            ],
        );

        $this->linkCatalog($autoParts, ['REP-001', 'REP-002', 'REP-003', 'REP-004', 'REP-005']);
        $this->linkCatalog($lubricentro, ['LUB-001', 'LUB-002', 'LUB-003', 'LUB-004', 'LUB-005']);
    }

    /**
     * @param  array<int, string>  $skus
     */
    private function linkCatalog(Supplier $supplier, array $skus): void
    {
        $products = Product::query()->whereIn('sku', $skus)->get();

        foreach ($products as $product) {
            $supplier->products()->syncWithoutDetaching([
                $product->id => [
                    'supplier_sku' => $supplier->id.'-'.$product->sku,
                    'last_unit_cost' => $product->last_purchase_cost,
                    'lead_time_days' => 3,
                    'availability_status' => SupplierAvailabilityStatus::Available->value,
                    'available_quantity' => 100,
                    'last_checked_at' => now(),
                    'is_preferred' => true,
                ],
            ]);
        }
    }
}
