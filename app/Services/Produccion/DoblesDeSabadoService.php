<?php

namespace App\Services\Produccion;

use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Produccion\TblProduccionHistorico;
use App\Models\TblInspCali;
use Illuminate\Support\Carbon;

/**
 * Sábados que se pagan doble.
 *
 * Un sábado cuenta doble cuando el inspector superó el tope de inspecciones
 * válidas del corte. Válida quiere decir que duró más de veinte minutos: por
 * debajo de eso no se considera una inspección real.
 *
 * Encima de la regla mandan dos listas que coordinación mantiene a mano sobre
 * el histórico del corte: los sábados que se añaden aunque no lleguen al tope,
 * y los que se excluyen aunque lo superen.
 */
class DoblesDeSabadoService
{
    /** Duración mínima, en minutos, para que una inspección cuente. */
    private const MINUTOS_MINIMOS = 20;

    /** ISO-8601: el sábado es el día 6. */
    private const SABADO = 6;

    /**
     * @param array<int, string> $diasFestivos Un sábado festivo no cuenta aparte.
     * @return array{contadorDiasSabados: int, sabadosdobles: array<int, array>}
     */
    public function calcular(
        TblInspCali $inspector,
        string $fechaInicio,
        string $fechaFin,
        array $diasFestivos,
        $tope,
        ?TblProduccionHistorico $historico
    ): array {
        $contador = 0;
        $dobles = [];

        $manuales = $this->decodificar($historico?->dobles_sabados);
        $excluidos = $this->decodificar($historico?->no_dobles);

        foreach ($this->contratosPorSabado($inspector, $fechaInicio, $fechaFin) as $sabado => $contratos) {
            if (in_array($sabado, $diasFestivos, true)) {
                continue;
            }

            foreach ($this->sabadosAnadidosAMano($manuales, $inspector->cedula, $sabado) as $anadido) {
                $dobles[] = $anadido;
            }

            if ($this->estaExcluido($excluidos, $inspector->cedula, $sabado)) {
                continue;
            }

            $validas = $this->inspeccionesValidas($contratos);

            if ($validas > $tope) {
                $calculados = $validas - $tope;
                $contador += $calculados;

                $dobles[] = [
                    'fecha'                => $sabado,
                    'cc_inspector'         => $inspector->cedula,
                    'totalContratosSabado' => $calculados,
                ];
            }
        }

        return ['contadorDiasSabados' => $contador, 'sabadosdobles' => $dobles];
    }

    /** Contratos del inspector agrupados por sábado. */
    private function contratosPorSabado(TblInspCali $inspector, string $desde, string $hasta)
    {
        return TblBitacoraContrato::where('CC_OPERARIO', $inspector->cedula)
            ->where('state', 1)
            ->whereBetween('FECHA', [$desde, $hasta])
            ->whereNotIn('TIPO_TRABAJO', ContratosDelCorteService::MATRICES)
            ->select('FECHA', 'HORA_INICIO', 'HORA_FINAL')
            ->get()
            ->filter(fn ($contrato) => Carbon::parse($contrato->FECHA)->dayOfWeekIso === self::SABADO)
            ->groupBy('FECHA');
    }

    /**
     * Inspecciones que duraron lo suficiente.
     *
     * Si la hora final es menor que la de inicio se asume que cruzó la
     * medianoche; sin eso la resta sale negativa y la inspección se pierde.
     */
    private function inspeccionesValidas($contratos): int
    {
        $validas = 0;

        foreach ($contratos as $contrato) {
            if (empty($contrato->HORA_INICIO) || empty($contrato->HORA_FINAL)) {
                continue;
            }

            $inicio = Carbon::parse($contrato->HORA_INICIO);
            $fin = Carbon::parse($contrato->HORA_FINAL);

            if ($fin->lt($inicio)) {
                $fin->addDay();
            }

            if ($inicio->diffInMinutes($fin) > self::MINUTOS_MINIMOS) {
                $validas++;
            }
        }

        return $validas;
    }

    /** Sábados que coordinación añadió a mano para este inspector y fecha. */
    private function sabadosAnadidosAMano(?array $manuales, $cedula, string $sabado): array
    {
        if ($manuales === null) {
            return [];
        }

        $encontrados = [];

        foreach ($manuales as $grupo) {
            foreach ($grupo as $registro) {
                if ($registro['datos']['cc_inspector'] != $cedula) {
                    continue;
                }

                foreach ($registro['datos']['totalInspecciones'] as $inspeccion) {
                    if ($inspeccion['fecha'] === $sabado) {
                        $encontrados[] = [
                            'fecha'        => $inspeccion['fecha'],
                            'cc_inspector' => $registro['datos']['cc_inspector'],
                        ];
                    }
                }
            }
        }

        return $encontrados;
    }

    /** ¿Coordinación excluyó este sábado a mano? */
    private function estaExcluido(?array $excluidos, $cedula, string $sabado): bool
    {
        if ($excluidos === null) {
            return false;
        }

        foreach ($excluidos as $grupo) {
            foreach ($grupo as $registro) {
                if ($cedula == $registro['datos']['cc_inspector']
                    && in_array($sabado, $registro['datos']['fechas'], false)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** El histórico guarda estas listas como JSON en una columna de texto. */
    private function decodificar($json): ?array
    {
        return $json === null ? null : json_decode($json, true);
    }
}
