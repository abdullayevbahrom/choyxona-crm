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
        Schema::table("orders", function (Blueprint $table) {
            $table->index(["status", "closed_at"], "orders_status_closed_at_idx");
            $table->index(["room_id", "closed_at"], "orders_room_closed_at_idx");
            $table->index(["user_id", "closed_at"], "orders_user_closed_at_idx");
            $table->index(["status", "updated_at"], "orders_status_updated_at_idx");
        });

        Schema::table("rooms", function (Blueprint $table) {
            $table->index(
                ["is_active", "status", "updated_at"],
                "rooms_active_status_updated_idx",
            );
        });

        Schema::table("menu_items", function (Blueprint $table) {
            $table->index(
                ["is_active", "name"],
                "menu_items_active_name_idx",
            );
            $table->index(
                ["is_active", "type", "name"],
                "menu_items_active_type_name_idx",
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("menu_items", function (Blueprint $table) {
            $table->dropIndex("menu_items_active_type_name_idx");
            $table->dropIndex("menu_items_active_name_idx");
        });

        Schema::table("rooms", function (Blueprint $table) {
            $table->dropIndex("rooms_active_status_updated_idx");
        });

        Schema::table("orders", function (Blueprint $table) {
            $table->dropIndex("orders_status_updated_at_idx");
            $table->dropIndex("orders_user_closed_at_idx");
            $table->dropIndex("orders_room_closed_at_idx");
            $table->dropIndex("orders_status_closed_at_idx");
        });
    }
};
