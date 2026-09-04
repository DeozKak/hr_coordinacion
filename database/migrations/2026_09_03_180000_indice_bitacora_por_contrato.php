<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice para buscar ejecutados en la bitácora de contratos.
 *
 * La tabla solo tenía la clave primaria y la del archivo, así que cada consulta
 * de findExecuted recorría las doscientas mil filas enteras. Eso se paga una vez
 * por contrato al programar a mano, una por fila en las cargas masivas y una por
 * programación pendiente en el cron, que es donde se notó: el comando tardaba
 * minutos.
 *
 * El orden de las columnas es el de la consulta: contrato descarta casi todo,
 * la orden termina de concretar y el tipo se resuelve dentro del índice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_bitacora_contratos', function (Blueprint $tabla) {
            $tabla->index(['CONTRATO', 'ORDEN_TRABAJO', 'TIPO_TRABAJO'], 'idx_bitacora_contrato_orden');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_bitacora_contratos', function (Blueprint $tabla) {
            $tabla->dropIndex('idx_bitacora_contrato_orden');
        });
    }
};
