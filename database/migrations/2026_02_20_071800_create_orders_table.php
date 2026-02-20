<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->string('order_number', 50)->unique();
            $table->enum('status', ['open', 'closed', 'cancelled'])->default('open');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['room_id', 'status']);
            $table->index(['status', 'opened_at']);
        });

        $driver = DB::getDriverName();

        if ($driver === 'sqlite' || $driver === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX orders_one_open_per_room_unique ON orders (room_id) WHERE status = 'open'");

            return;
        }

        if ($driver === 'mysql') {
            Schema::table('orders', function (Blueprint $table) {
                // MySQL doesn't support WHERE partial unique index; emulate it with a generated column.
                $table->unsignedBigInteger('open_room_id')
                    ->nullable()
                    ->storedAs("case when `status` = 'open' then `room_id` else null end");
                $table->unique('open_room_id', 'orders_one_open_per_room_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite' || $driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS orders_one_open_per_room_unique');
        }

        Schema::dropIfExists('orders');
    }
};
