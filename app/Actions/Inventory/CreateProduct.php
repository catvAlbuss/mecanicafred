<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $images
     */
    public function handle(array $attributes, User $user, array $images = []): Product
    {
        return DB::transaction(function () use ($attributes, $user, $images): Product {
            $initialStock = (string) Arr::pull($attributes, 'initial_stock', '0');
            Arr::forget($attributes, 'images');

            if (! is_numeric($initialStock)) {
                throw new \InvalidArgumentException('El stock inicial debe ser numérico.');
            }

            $product = Product::query()->create([
                ...$attributes,
                'current_stock' => $initialStock,
            ]);

            if (bccomp($initialStock, '0', 3) === 1) {
                $product->inventoryMovements()->create([
                    'user_id' => $user->id,
                    'type' => InventoryMovementType::OpeningBalance,
                    'quantity' => $initialStock,
                    'stock_before' => '0',
                    'stock_after' => $initialStock,
                    'reason' => 'Saldo inicial del producto',
                    'occurred_at' => now(),
                ]);
            }

            foreach ($images as $index => $image) {
                $product->addMedia($image)
                    ->withCustomProperties(['is_primary' => $index === 0])
                    ->toMediaCollection('images');
            }

            return $product;
        });
    }
}
