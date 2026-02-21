<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('history_at')
                ->nullable()
                ->storedAs('coalesce(`closed_at`, `opened_at`)');

            $table->index(['history_at', 'id'], 'orders_history_at_id_idx');
            $table->index(
                ['room_id', 'history_at', 'id'],
                'orders_room_history_at_id_idx',
            );
            $table->index(
                ['status', 'history_at', 'id'],
                'orders_status_history_at_id_idx',
            );
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_history_at_id_idx');
            $table->dropIndex('orders_room_history_at_id_idx');
            $table->dropIndex('orders_history_at_id_idx');
            $table->dropColumn('history_at');
        });
    }
};
