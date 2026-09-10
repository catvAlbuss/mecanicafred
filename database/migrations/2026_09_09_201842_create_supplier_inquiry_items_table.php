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
        Schema::create('supplier_inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_requested', 14, 3);
            $table->boolean('is_available')->nullable();
            $table->decimal('quantity_available', 14, 3)->nullable();
            $table->decimal('quoted_unit_cost', 14, 4)->nullable();
            $table->string('supplier_notes')->nullable();
            $table->timestamps();

            $table->unique(['supplier_inquiry_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_inquiry_items');
    }
};
