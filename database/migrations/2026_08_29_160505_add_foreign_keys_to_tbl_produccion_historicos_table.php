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
        Schema::table('tbl_produccion_historicos', function (Blueprint $table) {
            $table->foreign(['id_corte'], 'FR_Produccion_id')->references(['id'])->on('tbl_produccion_cortes')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_produccion_historicos', function (Blueprint $table) {
            $table->dropForeign('FR_Produccion_id');
        });
    }
};
