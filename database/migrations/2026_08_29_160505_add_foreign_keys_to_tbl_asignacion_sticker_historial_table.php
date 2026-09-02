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
        Schema::table('tbl_asignacion_sticker_historial', function (Blueprint $table) {
            $table->foreign(['id_inspector'], 'fr_inspector_asignacion_historico')->references(['id'])->on('tbl_insp_cali')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_sticker_tipo'], 'fr_sticker_tipo_historico')->references(['id'])->on('tbl_sticker_tipos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_usuario_asigna'], 'fr_user_sticker_historico')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_asignacion_sticker_historial', function (Blueprint $table) {
            $table->dropForeign('fr_inspector_asignacion_historico');
            $table->dropForeign('fr_sticker_tipo_historico');
            $table->dropForeign('fr_user_sticker_historico');
        });
    }
};
