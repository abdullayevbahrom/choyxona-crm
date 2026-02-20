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
        Schema::create("report_daily_room_summaries", function (Blueprint $table) {
            $table->id();
            $table->date("day");
            $table->foreignId("room_id")->constrained("rooms")->cascadeOnDelete();
            $table->unsignedInteger("orders_count")->default(0);
            $table->decimal("total_revenue", 14, 2)->default(0);
            $table->timestamps();

            $table->unique(["day", "room_id"], "report_daily_room_day_room_unique");
            $table->index(["room_id", "day"], "report_daily_room_room_day_idx");
        });

        Schema::create(
            "report_daily_cashier_summaries",
            function (Blueprint $table) {
                $table->id();
                $table->date("day");
                $table->foreignId("cashier_id")
                    ->constrained("users")
                    ->cascadeOnDelete();
                $table->unsignedInteger("orders_count")->default(0);
                $table->decimal("total_revenue", 14, 2)->default(0);
                $table->timestamps();

                $table->unique(
                    ["day", "cashier_id"],
                    "report_daily_cashier_day_cashier_unique",
                );
                $table->index(
                    ["cashier_id", "day"],
                    "report_daily_cashier_cashier_day_idx",
                );
            },
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("report_daily_cashier_summaries");
        Schema::dropIfExists("report_daily_room_summaries");
    }
};
