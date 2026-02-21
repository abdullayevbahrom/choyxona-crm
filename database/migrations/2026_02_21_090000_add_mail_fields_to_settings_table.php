<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('notification_from_name', 150)->nullable()->after('receipt_footer');
            $table->string('notification_from_email', 190)->nullable()->after('notification_from_name');
            $table->string('notification_logo_url', 255)->nullable()->after('notification_from_email');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'notification_from_name',
                'notification_from_email',
                'notification_logo_url',
            ]);
        });
    }
};
