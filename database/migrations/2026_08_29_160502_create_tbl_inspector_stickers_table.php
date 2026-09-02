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
        Schema::create('tbl_inspector_stickers', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->integer('id_inspector')->index('fr_inspector_asignacion');
            $table->bigInteger('id_sticker_tipo')->index('fr_sticker_tipo');
            $table->integer('cantidad_asignada');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_inspector_stickers');
    }
};
