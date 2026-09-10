<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Seed the four demonstration categories used across the inventory module.
     */
    public function run(): void
    {
        $categories = [
            ['slug' => 'lubricantes', 'name' => 'Lubricantes', 'type' => ProductType::Lubricant, 'description' => 'Aceites y grasas para motor y transmisión.'],
            ['slug' => 'repuestos', 'name' => 'Repuestos', 'type' => ProductType::SparePart, 'description' => 'Piezas de reemplazo para mantenimiento.'],
            ['slug' => 'herramientas', 'name' => 'Herramientas', 'type' => ProductType::Tool, 'description' => 'Herramientas de uso diario en el taller.'],
            ['slug' => 'consumibles', 'name' => 'Consumibles', 'type' => ProductType::Consumable, 'description' => 'Insumos de un solo uso para el servicio.'],
        ];

        foreach ($categories as $category) {
            ProductCategory::query()->firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'description' => $category['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
