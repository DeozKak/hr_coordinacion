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
        Schema::create('job_status', function (Blueprint $table) {
            $table->id();
            $table->string('job_name'); // Nombre del Job o Command
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending'); // Estado
            $table->unsignedInteger('total')->nullable(); // Total de registros a procesar
            $table->unsignedInteger('processed')->nullable(); // Progresados hasta ahora
            $table->text('details')->nullable(); // Detalles adicionales o errores
            $table->timestamps(); // Marcas de tiempo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_status');
    }
};
