<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);

        if (app()->environment('local')) {
            User::firstOrCreate(
                ['email' => 'admin@fredyracing.test'],
                [
                    'name' => 'Administrador Fredy Racing',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            )->assignRole('Administrador');

            $this->call([
                ProductCategorySeeder::class,
                ProductSeeder::class,
                SupplierSeeder::class,
                PurchaseOrderSeeder::class,
            ]);
        }
    }
}
