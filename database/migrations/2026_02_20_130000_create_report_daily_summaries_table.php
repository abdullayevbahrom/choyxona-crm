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
        Schema::create('report_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('day')->unique();
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->timestamps();

            $table->index('day', 'report_daily_summaries_day_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_daily_summaries');
    }
};
