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
        Schema::create('tbl_nomina_multas', function (Blueprint $table) {
            $table->id()->autoIncrement()->primary(true);
            $table->string('cc_operario', 30);
            $table->integer('multa');
            $table->integer('rodamiento');
            $table->string('fecha', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_nomina_multas');
    }
};
