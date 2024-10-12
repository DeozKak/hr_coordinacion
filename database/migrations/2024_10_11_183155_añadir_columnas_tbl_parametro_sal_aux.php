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
        Schema::table('tbl_parametro_sal_aux', function (Blueprint $table) {
            $table->string('salud')->after('auxilio_transporte');
            $table->string('pension')->after('salud');
            $table->string('arl')->after('pension');
            $table->string('caja')->after('arl');
            $table->string('prima')->after('caja');
            $table->string('cesantias')->after('prima');
            $table->string('intCesantias')->after('cesantias');
            $table->string('vacaciones')->after('insCesantias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_parametro_sal_aux', function (Blueprint $table) {
            // Eliminar las columnas agregadas en esta migración
            $table->dropColumn(['salud', 'pension', 'arl', 'caja', 'prima', 'cesantias', 'insCesantias', 'vacaciones']);
        });
    }
};
