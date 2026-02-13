<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique(); // El ID del usuario (su teléfono)
            $table->string('step')->default('START'); // En qué paso va (MENU, OPCION_1, etc)
            $table->json('temp_data')->nullable(); // Para guardar datos temporales (ej: nombre)
            $table->timestamp('last_activity'); // Para medir el tiempo de inactividad
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
