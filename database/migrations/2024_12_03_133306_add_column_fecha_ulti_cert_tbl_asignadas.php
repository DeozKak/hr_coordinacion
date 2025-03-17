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
            $table->date('fecha_ult_cert')->after('id_cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignadas', function(Blueprint $table){
            $table->dropColumn('fecha_ult_cert');
        });
    }
};
