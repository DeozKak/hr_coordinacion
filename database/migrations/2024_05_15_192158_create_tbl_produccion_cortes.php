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
        Schema::create('tbl_produccion_cortes', function (Blueprint $table) {
            $table->id()->primary(true)->autoIncrement();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_produccion_corte');
    }
};
