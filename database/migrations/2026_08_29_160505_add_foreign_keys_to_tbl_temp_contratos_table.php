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
        Schema::table('tbl_temp_contratos', function (Blueprint $table) {
            $table->foreign(['id_bitacora'], 'FR_ARCHIVO')->references(['id'])->on('tbl_bitacora_archivos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_usuario'], 'FR_USER')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_temp_contratos', function (Blueprint $table) {
            $table->dropForeign('FR_ARCHIVO');
            $table->dropForeign('FR_USER');
        });
    }
};
