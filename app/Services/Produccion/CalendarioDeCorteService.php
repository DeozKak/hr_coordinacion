<?php

namespace App\Services\Produccion;

use App\Models\Produccion\TblProduccionCorte;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Rmunate\Calendario\CalendarioColombia;

/**
 * Los días de un corte: cómo se rotulan, cuáles son festivos y cuáles sábados.
 */
class CalendarioDeCorteService
{
    private const DIAS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    private const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    /**
     * Festivos que la librería no trae.
     *
     * Días no laborales decretados fuera del calendario oficial. Se añaden a
     * mano conforme aparecen.
     */
    private const FESTIVOS_EXTRAORDINARIOS = [
        '2026-07-13',
    ];

    /**
     * Cabecera de la tabla: un elemento por día del corte.
     *
     * @return array<int, array{dias: string, nombreDia: string, nombreMes: string}>|null
     */
    public function diasIntermedios(?TblProduccionCorte $corte): ?array
    {
        if ($corte === null) {
            return null;
        }

        return array_map(fn (string $fecha) => [
            'dias'      => Carbon::parse($fecha)->format('d'),
            'nombreDia' => self::DIAS[(int) Carbon::parse($fecha)->format('w')],
            'nombreMes' => self::MESES[(int) Carbon::parse($fecha)->format('n')],
        ], $this->fechas($corte));
    }

    /**
     * Todas las fechas del corte, de inicio a fin, en formato Y-m-d.
     *
     * @return array<int, string>
     */
    public function fechas(TblProduccionCorte $corte): array
    {
        $fechas = [];
        $dia = Carbon::parse($corte->fecha_inicio);
        $fin = Carbon::parse($corte->fecha_fin);

        while ($dia->lte($fin)) {
            $fechas[] = $dia->format('Y-m-d');
            $dia->addDay();
        }

        return $fechas;
    }

    /**
     * Festivos del corte y del mes anterior.
     *
     * Se guarda en caché porque preguntar día a día a la librería es caro y el
     * calendario no cambia durante el corte. La clave lleva el rango, así que
     * un corte distinto no reutiliza la respuesta de otro.
     *
     * @return array<int, string>
     */
    public function diasFestivos(TblProduccionCorte $corte): array
    {
        $inicio = Carbon::parse($corte->fecha_inicio);
        $fin = Carbon::parse($corte->fecha_fin);
        $desde = $inicio->copy()->subMonth();

        $clave = 'dias_festivos_rango_' . $desde->format('Ymd') . '_' . $fin->format('Ymd');

        return Cache::remember($clave, $inicio->diffInMinutes($fin), function () use ($desde, $fin) {
            $festivos = [];

            for ($dia = $desde->copy(); $dia->lte($fin); $dia->addDay()) {
                $fecha = $dia->format('Y-m-d');

                if (CalendarioColombia::date($fecha)->isHoliday()
                    || in_array($fecha, self::FESTIVOS_EXTRAORDINARIOS, true)) {
                    $festivos[] = $fecha;
                }
            }

            return $festivos;
        });
    }

    /**
     * La plantilla de días del corte, con todas las fechas en blanco.
     *
     * Sobre ella se van escribiendo los contratos de cada inspector, para que
     * todas las filas tengan las mismas columnas aunque el inspector no haya
     * trabajado todos los días.
     *
     * @return array<string, string>
     */
    public function plantillaDeFechas(TblProduccionCorte $corte): array
    {
        return array_fill_keys($this->fechas($corte), '');
    }
}
