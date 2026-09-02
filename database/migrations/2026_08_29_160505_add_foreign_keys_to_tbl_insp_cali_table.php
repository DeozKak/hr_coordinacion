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
        Schema::table('tbl_insp_cali', function (Blueprint $table) {
            $table->foreign(['SUPERVISOR'], 'id_superv_insp')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_insp_cali', function (Blueprint $table) {
            $table->dropForeign('id_superv_insp');
        });
    }
};
