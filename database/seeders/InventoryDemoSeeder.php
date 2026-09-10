<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryDemoSeeder extends Seeder
{
    /**
     * Seed the inventory demonstration data in dependency order:
     * categories, then products, then suppliers and their catalog.
     */
    public function run(): void
    {
        $this->call([
            ProductCategorySeeder::class,
            ProductSeeder::class,
            SupplierSeeder::class,
        ]);
    }
}
