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
        Schema::create('tbl_parametro_sal_aux', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fecha_inicio', 30);
            $table->string('fecha_fin', 30);
            $table->integer('salario_minimo');
            $table->integer('auxilio_transporte');
            $table->string('salud');
            $table->string('pension');
            $table->string('arl');
            $table->string('caja');
            $table->string('prima');
            $table->string('cesantias');
            $table->string('intCesantias');
            $table->string('vacaciones');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_parametro_sal_aux');
    }
};
