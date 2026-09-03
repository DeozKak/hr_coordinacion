<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cortes de GDO: el periodo sobre el que se mide la legalización.
 *
 * Se guarda el histórico y no un solo registro editable, porque el corte
 * anterior sigue siendo la referencia de lo que se reportó en su momento.
 * El vigente es el que contiene el día de hoy; si ninguno lo contiene, el
 * último que se haya cerrado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_cortes_gdo', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->date('fecha_inicio');
            $tabla->date('fecha_fin');
            // Quién lo definió. Sin llave foránea: users se administra aparte
            // y un usuario dado de baja no debe arrastrarse el corte.
            $tabla->unsignedBigInteger('creado_por')->nullable();
            $tabla->timestamps();

            // Se consulta siempre buscando el que cubre una fecha.
            $tabla->index(['fecha_inicio', 'fecha_fin'], 'idx_rango');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_cortes_gdo');
    }
};
