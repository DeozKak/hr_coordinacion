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
        Schema::create('tbl_insp_cali', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nombres', 50);
            $table->string('apellidos', 50);
            $table->string('type_id', 10);
            $table->string('cedula', 20)->unique('cedula_unica');
            $table->tinyInteger('aprendiz')->nullable()->default(1);
            $table->unsignedBigInteger('SUPERVISOR')->index('id_superv_insp');
            $table->boolean('state');
            $table->date('created_at')->nullable();
            $table->date('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_insp_cali');
    }
};
