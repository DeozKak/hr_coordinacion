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
        Schema::create('tbl_sticker_inventario', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('id_sticker_tipo')->index('fr_sktickertipo_cant');
            $table->integer('cantidad_disponible')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_sticker_inventario');
    }
};
