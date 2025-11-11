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
        Schema::create('tbl_sticker_acta_seriales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sticker_tipo'); // FK a tbl_sticker_tipo (debe ser el ID de "ACTA")
            $table->string('serial')->unique(); // El número de serial. Lo hacemos único.

            $table->enum('estado', ['en_inventario', 'asignado', 'utilizado', 'anulado'])->default('en_inventario');

            $table->integer('id_inspector')->nullable(); // FK a tbl_insp_cali

            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_sticker_tipo')->references('id')->on('tbl_sticker_tipos');
            $table->foreign('id_inspector')->references('id')->on('tbl_insp_cali');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_sticker_acta_seriales');
    }
};
