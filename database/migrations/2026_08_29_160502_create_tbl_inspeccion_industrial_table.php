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
        Schema::create('tbl_inspeccion_industrial', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fecha');
            $table->integer('cantidad');
            $table->integer('total');
            $table->integer('metagyc');
            $table->integer('metagdo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_inspeccion_industrial');
    }
};
