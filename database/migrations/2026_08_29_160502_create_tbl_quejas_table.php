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
        Schema::create('tbl_quejas', function (Blueprint $table) {
            $table->string('CONTRATO');
            $table->string('LOCALIDAD');
            $table->string('BARRIO');
            $table->string('DIRECCION');
            $table->string('INSPECTOR');
            $table->integer('DIAS')->default(0);
            $table->string('recepcion')->nullable();
            $table->bigInteger('id', true)->index('id');
            $table->date('created_at');
            $table->date('updated_at');

            $table->primary(['id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_quejas');
    }
};
