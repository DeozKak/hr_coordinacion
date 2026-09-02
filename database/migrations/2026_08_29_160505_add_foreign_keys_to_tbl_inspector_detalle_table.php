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
        Schema::table('tbl_inspector_detalle', function (Blueprint $table) {
            $table->foreign(['detalle_id'], 'FR_detalle')->references(['id'])->on('tbl_grupos_detalle')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['inspector_id'], 'FR_insp')->references(['id'])->on('tbl_insp_cali')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_inspector_detalle', function (Blueprint $table) {
            $table->dropForeign('FR_detalle');
            $table->dropForeign('FR_insp');
        });
    }
};
