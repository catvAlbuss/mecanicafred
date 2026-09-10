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
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_sku')->nullable();
            $table->decimal('last_unit_cost', 14, 4)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->string('availability_status')->default('unknown');
            $table->decimal('available_quantity', 14, 3)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'supplier_id']);
            $table->index(['supplier_id', 'availability_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
