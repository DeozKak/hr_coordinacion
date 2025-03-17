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
        Schema::table('asignadas', function(Blueprint $table){
            $table->integer('causa_cierre')->after('fecha_asignacion_inspector')->nullable();
            $table->string('fecha_solicitud_cierre')->after('causa_cierre')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignadas', function(Blueprint $table){
            $table->dropColumn([
                'causa_cierre',
                'fecha_solicitud_cierre'
            ]);
        });
    }
};
