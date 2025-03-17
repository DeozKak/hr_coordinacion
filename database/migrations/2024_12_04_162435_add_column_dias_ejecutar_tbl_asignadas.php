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
        Schema::table('asignadas', function(Blueprint $table){
            $table->string('dias_ejecutar', 30)->after('dias_proceso')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignadas', function(Blueprint $table){
            $table->dropColumn('dias_ejecutar');
        });
    }
};
