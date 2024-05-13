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
        Schema::create('tbl_bitacora_archivos', function (Blueprint $table) {
            $table->id()->primary()->autoIncrement();
            $table->foreignId('id_usuario')->constrained('users');
            $table->string('nombre_archivo');
            $table->string('ruta_archivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_bitacora_archivos');
    }
};
