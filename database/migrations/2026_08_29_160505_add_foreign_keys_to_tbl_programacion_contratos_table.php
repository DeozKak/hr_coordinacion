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
        Schema::table('tbl_programacion_contratos', function (Blueprint $table) {
            $table->foreign(['id_programacion'], 'FR_PROGRAMACION_USUARIO')->references(['id'])->on('tbl_programacion_usuarios')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_programacion_contratos', function (Blueprint $table) {
            $table->dropForeign('FR_PROGRAMACION_USUARIO');
        });
    }
};
