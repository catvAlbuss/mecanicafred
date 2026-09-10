<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->restrictOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode', 32)->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('unit')->default('unit');
            $table->decimal('minimum_stock', 14, 3)->default(0);
            $table->decimal('current_stock', 14, 3)->default(0);
            $table->string('location')->nullable();
            $table->decimal('last_purchase_cost', 14, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
