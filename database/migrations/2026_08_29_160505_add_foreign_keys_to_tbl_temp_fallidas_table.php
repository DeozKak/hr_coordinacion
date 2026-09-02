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
        Schema::table('tbl_temp_fallidas', function (Blueprint $table) {
            $table->foreign(['id_bitacora'], 'fr_bitacora_fallidas')->references(['id'])->on('tbl_bitacora_archivos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_usuario'], 'fr_usuario_fallidas')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_temp_fallidas', function (Blueprint $table) {
            $table->dropForeign('fr_bitacora_fallidas');
            $table->dropForeign('fr_usuario_fallidas');
        });
    }
};
