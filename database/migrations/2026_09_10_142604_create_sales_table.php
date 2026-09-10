<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('status')->default('completed');
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('sold_by')->constrained('users')->restrictOnDelete();
            $table->string('payment_method');
            $table->string('customer_name')->nullable();
            $table->string('customer_document', 20)->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('sold_at');
            $table->timestamps();

            $table->index(['status', 'sold_at']);
            $table->index(['cash_register_id', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
