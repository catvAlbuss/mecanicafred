<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->string('category');
            $table->string('payment_method');
            $table->decimal('amount', 14, 2);
            $table->string('description');
            $table->string('reference')->nullable();
            $table->nullableMorphs('source');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['cash_register_id', 'occurred_at']);
            $table->index(['type', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
