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
        Schema::table('tbl_inspeccion_industrial', function (Blueprint $table) {
            $table->integer('total')->after('cantidad')->change();

            $table->integer('metagyc')->after('total');
            $table->integer('metagdo')->after('metagyc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_inspeccion_industrial', function (Blueprint $table) {
            // Eliminar las columnas agregadas en esta migración
            $table->dropColumn(['metagyc', 'metagdo']);
        });
    }
};
