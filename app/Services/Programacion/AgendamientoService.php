<?php

namespace App\Services\Programacion;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consulta de lo agendado para una fecha o un rango.
 *
 * Junta dos orígenes: lo programado de verdad —contratos que cruzan con la
 * base y cuya programación está cerrada— y lo que se dio de alta a mano desde
 * una plantilla, que no tiene contrato en la base y por eso no aparece en el
 * cruce. La plantilla va primero: si un id sale por los dos lados, manda ella.
 */
class AgendamientoService
{
    /** Lo que consume la rejilla. Sin marcas de tiempo ni banderas internas. */
    private const COLUMNAS = [
        'id', 'CONTRATO', 'TIPO_TRABAJO', 'FECHA', 'CELULAR', 'NOMBRE_USUARIO',
        'ORDEN_TRABAJO', 'DIRECCION', 'BARRIO', 'CIUDAD', 'ACTIVA', 'SUSPENDIDO',
        'CATEGORIA', 'FECHA_AGENDAMIENTO', 'OBSERVACIONES', 'PORQUE_PROGRAMO',
        'TECNICO', 'JORNADA',
    ];

    /**
     * Filas agendadas y los nombres de columna que espera la rejilla.
     *
     * @return array{data: Collection, columnas: array}
     */
    public function consultar(string $fechaInicio, ?string $fechaFin = null): array
    {
        $plantilla = $this->deLaPlantilla($fechaInicio, $fechaFin)->orderBy('TECNICO')->get();
        $programado = $this->programado($fechaInicio, $fechaFin)->orderBy('TECNICO')->get();

        return [
            // unique() conserva el primero, y la plantilla va delante.
            'data'     => $plantilla->concat($programado)->unique('id')->values(),
            'columnas' => $this->columnasDeLaTabla(),
        ];
    }

    /**
     * Lo programado que cruza con la base y ya está cerrado.
     *
     * Ojo a una diferencia que viene de antes y se conserva tal cual: buscando
     * un solo día se exige ESTADO_RECEPCION = 0, mientras que en un rango se
     * admite además que venga en blanco. Cambiarlo alteraría los totales que
     * hoy se reportan, así que se deja como está y se deja dicho.
     */
    private function programado(string $fechaInicio, ?string $fechaFin)
    {
        $consulta = DB::table('tbl_programacion_contratos AS pc')
            ->join('tbl_programacion_base AS pb', 'pc.CONTRATO', '=', 'pb.CONTRATO')
            ->join('tbl_programacion_usuarios AS pu', 'pc.id_programacion', '=', 'pu.id')
            ->where('pc.EJECUTADA', '=', 0)
            ->where('pu.finished', 1)
            ->select($this->columnasConAlias('pc'));

        if ($fechaFin === null) {
            return $consulta
                ->where('pc.FECHA_AGENDAMIENTO', '=', $fechaInicio)
                ->where('pb.ESTADO_RECEPCION', '=', 0);
        }

        return $consulta->where(function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('FECHA_AGENDAMIENTO', [$fechaInicio, $fechaFin])
                ->where(fn ($sub) => $sub->where('pb.ESTADO_RECEPCION', '=', 0)
                    ->orWhereNull('pb.ESTADO_RECEPCION'));
        });
    }

    /** Lo dado de alta a mano desde una plantilla. */
    private function deLaPlantilla(string $fechaInicio, ?string $fechaFin)
    {
        $consulta = DB::table('tbl_programacion_contratos')
            ->where('EJECUTADA', '=', 0)
            ->where('plantilla', 1)
            ->select(self::COLUMNAS);

        return $fechaFin === null
            ? $consulta->where('FECHA_AGENDAMIENTO', '=', $fechaInicio)
            : $consulta->where('FECHA_AGENDAMIENTO', '>=', $fechaInicio)
                       ->where('FECHA_AGENDAMIENTO', '<=', $fechaFin);
    }

    /** @return array<int, string> */
    private function columnasConAlias(string $alias): array
    {
        return array_map(fn (string $c) => "{$alias}.{$c}", self::COLUMNAS);
    }

    /**
     * Nombres de columna para la cabecera de la rejilla.
     *
     * Salen del esquema y no de una lista escrita a mano, pero con un retoque:
     * la columna 19 se mueve a la posición 17 para que JORNADA quede junto a
     * las horas, que es el orden en el que se lee la tabla.
     */
    private function columnasDeLaTabla(): array
    {
        $columnas = Schema::getColumnListing('tbl_programacion_contratos');

        $movida = array_splice($columnas, 19, 1);
        array_splice($columnas, 17, 0, $movida);

        return array_diff($columnas, ['updated_at', 'created_at']);
    }
}
