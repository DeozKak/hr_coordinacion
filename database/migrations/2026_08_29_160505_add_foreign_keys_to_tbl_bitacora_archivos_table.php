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
        Schema::table('tbl_bitacora_archivos', function (Blueprint $table) {
            $table->foreign(['id_usuario'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_bitacora_archivos', function (Blueprint $table) {
            $table->dropForeign('tbl_bitacora_archivos_id_usuario_foreign');
        });
    }
};
