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
        Schema::create('tbl_asignacion_tecnicos_localidad', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('localidad')->nullable();
            $table->bigInteger('id_tecnico')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_asignacion_tecnicos_localidad');
    }
};
