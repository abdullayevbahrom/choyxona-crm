<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_waiters', function (Blueprint $table) {
            $table->index(
                ['user_id', 'order_id'],
                'order_waiters_user_order_idx',
            );
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->index(['name'], 'rooms_name_idx');
            $table->index(
                ['is_active', 'status', 'capacity'],
                'rooms_active_status_capacity_idx',
            );
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'name'], 'users_role_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_name_idx');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('rooms_active_status_capacity_idx');
            $table->dropIndex('rooms_name_idx');
        });

        Schema::table('order_waiters', function (Blueprint $table) {
            $table->dropIndex('order_waiters_user_order_idx');
        });
    }
};
