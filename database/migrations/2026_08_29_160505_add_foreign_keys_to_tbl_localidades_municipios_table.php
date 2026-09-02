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
        Schema::table('tbl_localidades_municipios', function (Blueprint $table) {
            $table->foreign(['id_sede'])->references(['id'])->on('tbl_localidades_sedes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_zona'], 'tbl_produccion_zona')->references(['id'])->on('tbl_produccion_zonas')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_localidades_municipios', function (Blueprint $table) {
            $table->dropForeign('tbl_localidades_municipios_id_sede_foreign');
            $table->dropForeign('tbl_produccion_zona');
        });
    }
};
