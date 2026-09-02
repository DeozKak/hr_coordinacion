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
        Schema::table('tbl_bitacora_contratos', function (Blueprint $table) {
            $table->foreign(['id_bitacora'])->references(['id'])->on('tbl_bitacora_archivos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_bitacora_contratos', function (Blueprint $table) {
            $table->dropForeign('tbl_bitacora_contratos_id_bitacora_foreign');
        });
    }
};
