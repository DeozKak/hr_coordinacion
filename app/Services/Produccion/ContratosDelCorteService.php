<?php

namespace App\Services\Produccion;

use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Produccion\TblProduccionCorte;
use App\Models\TblInspCali;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Contratos de bitácora que cuentan como producción de un corte.
 *
 * Concentra el filtro que antes estaba copiado en ocho sitios del controlador:
 * dentro del rango del corte, activos y sin las revisiones de línea matriz, que
 * se pagan aparte y no entran en la producción del inspector.
 */
class ContratosDelCorteService
{
    /**
     * Trabajos que no cuentan como producción.
     *
     * Se comparan por el texto exacto que trae la bitácora; el acento y las
     * mayúsculas son los del archivo original y no se pueden normalizar sin
     * dejar de casar con los datos ya cargados.
     */
    public const MATRICES = [
        'FI-29 revisión periódica línea matriz',
        'FI-31 REVISIÓN NUEVA LINEA MATRIZ',
    ];

    /** El corte que cubre el día de ayer, que es el que se muestra por defecto. */
    public function vigente(): ?TblProduccionCorte
    {
        $ayer = Carbon::yesterday()->toDateString();

        return TblProduccionCorte::where('fecha_inicio', '<=', $ayer)
            ->where('fecha_fin', '>=', $ayer)
            ->first();
    }

    /** Consulta base de los contratos de un corte. */
    public function consulta(TblProduccionCorte $corte): Builder
    {
        return TblBitacoraContrato::query()
            ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
            ->where('state', 1);
    }

    /** La misma consulta, ya sin las líneas matriz. */
    public function consultaSinMatrices(TblProduccionCorte $corte): Builder
    {
        return $this->consulta($corte)->whereNotIn('TIPO_TRABAJO', self::MATRICES);
    }

    /** Inspectores con producción en el corte, ordenados por apellido. */
    public function inspectoresConProduccion(TblProduccionCorte $corte, bool $sinMatrices = true): Collection
    {
        return TblInspCali::whereHas('contratos', function ($consulta) use ($corte, $sinMatrices) {
            $consulta->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
                ->where('state', 1);

            if ($sinMatrices) {
                $consulta->whereNotIn('TIPO_TRABAJO', self::MATRICES);
            }
        })->orderBy('apellidos')->get();
    }

    /**
     * Contratos de cada inspector en el corte.
     *
     * @return array<int, array{nombres: string, contratos: int, cedula: string}>
     */
    public function produccionPorInspector(TblProduccionCorte $corte, Collection $inspectores): array
    {
        $porCedula = $this->consultaSinMatrices($corte)
            ->whereIn('CC_OPERARIO', $inspectores->pluck('cedula'))
            ->selectRaw('CC_OPERARIO, COUNT(*) AS total')
            ->groupBy('CC_OPERARIO')
            ->pluck('total', 'CC_OPERARIO');

        return $inspectores->map(fn ($inspector) => [
            'nombres'   => $inspector->apellidos,
            'contratos' => (int) ($porCedula[$inspector->cedula] ?? 0),
            'cedula'    => $inspector->cedula,
        ])->all();
    }

    /** Total de contratos del corte. */
    public function total(TblProduccionCorte $corte): int
    {
        return $this->consultaSinMatrices($corte)->count();
    }

    /** El nombre con el que se rotula un corte en los selectores. */
    public function etiqueta(TblProduccionCorte $corte): string
    {
        return $corte->nombre . ' '
            . explode('-', $corte->fecha_inicio)[0] . '-'
            . explode('-', $corte->fecha_fin)[0];
    }

    /**
     * Municipios de la bitácora que no existen en la tabla de localidades.
     *
     * Se avisa en pantalla porque un municipio sin zona no entra en el reporte
     * por zonas y el descuadre aparece más tarde y sin explicación.
     */
    public function municipiosSinLocalidad(TblProduccionCorte $corte): \Illuminate\Support\Collection
    {
        $enBitacora = $this->consultaSinMatrices($corte)
            ->distinct()
            ->pluck('MUNICIPIO');

        $conocidos = \App\Models\Zonificacion\TblLocalidadesMunicipio::distinct()->pluck('nombre');

        return $enBitacora->diff($conocidos)->unique()->values();
    }
}
