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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->string('bill_number', 50)->unique();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->enum('payment_method', ['cash', 'card', 'transfer'])->nullable();
            $table->boolean('is_printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
