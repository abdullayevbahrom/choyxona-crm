<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $table->json('filters')->nullable();
            $table->string('format', 20)->default('csv');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'report_exports_user_created_idx');
            $table->index(['status', 'created_at'], 'report_exports_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
