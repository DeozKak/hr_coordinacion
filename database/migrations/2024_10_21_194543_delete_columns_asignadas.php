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
        Schema::table('asignadas', function (Blueprint $table) {
            $table->dropColumn('fecha_externa');
            $table->dropColumn('orden_externa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignadas', function (Blueprint $table) {
            $table->date('fecha_externa')->nullable();
            $table->int('orden_externa')->nullable();
        });
    }
};
