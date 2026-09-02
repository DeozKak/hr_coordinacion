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
        Schema::table('tbl_notifications_has_users', function (Blueprint $table) {
            $table->foreign(['id_notification'], 'notification_has_user')->references(['id'])->on('tbl_notificaciones')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_user'], 'user_has_notification')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_notifications_has_users', function (Blueprint $table) {
            $table->dropForeign('notification_has_user');
            $table->dropForeign('user_has_notification');
        });
    }
};
