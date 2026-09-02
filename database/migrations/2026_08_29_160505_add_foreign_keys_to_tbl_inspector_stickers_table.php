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
        Schema::table('tbl_inspector_stickers', function (Blueprint $table) {
            $table->foreign(['id_inspector'], 'fr_inspector_asignacion')->references(['id'])->on('tbl_insp_cali')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_sticker_tipo'], 'fr_sticker_tipo')->references(['id'])->on('tbl_sticker_tipos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_inspector_stickers', function (Blueprint $table) {
            $table->dropForeign('fr_inspector_asignacion');
            $table->dropForeign('fr_sticker_tipo');
        });
    }
};
