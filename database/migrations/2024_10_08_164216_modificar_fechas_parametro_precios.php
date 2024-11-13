<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_parametro_precios', function (Blueprint $table) {
            // Cambiar el tipo de las columnas de 'date' a 'varchar'
            $table->string('fecha_inicio', 30)->change();
            $table->string('fecha_fin', 30)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_parametro_precios', function (Blueprint $table) {
            // Revertir los cambios volviendo a 'date'
            $table->date('fecha_inicio')->change();
            $table->date('fecha_fin')->change();
        });
    }
};
