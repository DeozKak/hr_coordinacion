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
        Schema::table('tbl_dv_insp', function (Blueprint $table) {
            $table->foreign(['id_bitacora'], 'FR_BITACORA_DV')->references(['id'])->on('tbl_bitacora_archivos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['INSPECTOR'], 'FR_INSP_DV')->references(['id'])->on('tbl_insp_cali')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['SUPERVISOR'], 'FR_SUPER_DV')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_dv_insp', function (Blueprint $table) {
            $table->dropForeign('FR_BITACORA_DV');
            $table->dropForeign('FR_INSP_DV');
            $table->dropForeign('FR_SUPER_DV');
        });
    }
};
