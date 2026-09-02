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
            $table->bigIncrements('id');
            $table->bigInteger('id_sticker_tipo')->index('fr_tipo');
            $table->string('serial')->unique('serial');
            $table->enum('estado', ['en_inventario', 'asignado', 'utilizado', 'anulado'])->default('en_inventario');
            $table->integer('id_inspector')->nullable()->index('fr_inspector_acta');
            $table->timestamps();
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
