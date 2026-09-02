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
        Schema::table('tbl_grupos', function (Blueprint $table) {
            $table->foreign(['id_sede'], 'sedes_has_grupos')->references(['id'])->on('tbl_localidades_sedes')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_grupos', function (Blueprint $table) {
            $table->dropForeign('sedes_has_grupos');
        });
    }
};
