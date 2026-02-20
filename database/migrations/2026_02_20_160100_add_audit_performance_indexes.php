<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("activity_logs", function (Blueprint $table) {
            $table->index(
                ["subject_type", "subject_id", "created_at"],
                "activity_logs_subject_created_idx",
            );
        });

        Schema::table("orders", function (Blueprint $table) {
            $table->index(
                ["status", "room_id", "closed_at"],
                "orders_status_room_closed_idx",
            );
            $table->index(
                ["status", "user_id", "closed_at"],
                "orders_status_user_closed_idx",
            );
        });
    }

    public function down(): void
    {
        Schema::table("orders", function (Blueprint $table) {
            $table->dropIndex("orders_status_user_closed_idx");
            $table->dropIndex("orders_status_room_closed_idx");
        });

        Schema::table("activity_logs", function (Blueprint $table) {
            $table->dropIndex("activity_logs_subject_created_idx");
        });
    }
};
