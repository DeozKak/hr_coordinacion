<?php

namespace App\Services\Produccion;

use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Produccion\TblProduccionHistorico;

/**
 * Los ajustes que coordinación hace a mano sobre los dobles de un corte.
 *
 * Son tres listas, cada una en una columna JSON de tbl_produccion_historico:
 *
 *   no_dobles           sábados que NO se pagan doble aunque el cálculo diga que sí
 *   no_dobles_festivos  festivos que NO se pagan doble
 *   dobles_sabados      sábados que SÍ se pagan doble aunque no lleguen al tope
 *
 * Las tres guardan lo mismo —una entrada por inspector con sus fechas—, así que
 * las seis operaciones que había en el controlador se reducen a poner y quitar.
 */
class AjustesDeDoblesService
{
    /** Cada lista, con la clave con la que se guarda dentro del JSON. */
    private const LISTAS = [
        'no_dobles'          => 'noDobles',
        'no_dobles_festivos' => 'noDoblesFestivos',
        'dobles_sabados'     => 'doblesSabados',
    ];

    /** Un festivo deja de pagarse doble. */
    public function excluirFestivo(TblProduccionHistorico $historico, $cedula, string $fecha): void
    {
        $this->anadirInspeccion($historico, 'no_dobles_festivos', $cedula, $fecha, $this->inspeccionesDelDia($cedula, $fecha));
    }

    /** Deshace lo anterior. */
    public function incluirFestivo(TblProduccionHistorico $historico, $cedula, string $fecha): void
    {
        $this->quitarInspeccion($historico, 'no_dobles_festivos', $cedula, $fecha);
    }

    /** Un sábado pasa a pagarse doble con la cantidad indicada. */
    public function anadirSabado(TblProduccionHistorico $historico, $cedula, string $fecha, $cantidad): void
    {
        $this->anadirInspeccion($historico, 'dobles_sabados', $cedula, $fecha, $cantidad);
    }

    /** Deshace lo anterior. */
    public function quitarSabado(TblProduccionHistorico $historico, $cedula, string $fecha): void
    {
        $this->quitarInspeccion($historico, 'dobles_sabados', $cedula, $fecha);
    }

    /** Un día deja de contar doble. Esta lista guarda fechas sueltas, sin cantidad. */
    public function excluirDia(TblProduccionHistorico $historico, $cedula, string $fecha): void
    {
        $lista = $this->leer($historico, 'no_dobles');
        $registro = &$this->registroDe($lista, 'noDobles', $cedula, ['fechas' => []]);

        if (! in_array($fecha, $registro['datos']['fechas'], true)) {
            $registro['datos']['fechas'][] = $fecha;
        }

        unset($registro);
        $this->guardar($historico, 'no_dobles', $lista);
    }

    /** Deshace lo anterior. */
    public function incluirDia(TblProduccionHistorico $historico, $cedula, string $fecha): void
    {
        $lista = $this->leer($historico, 'no_dobles');

        foreach ($lista['noDobles'] ?? [] as &$registro) {
            if ($cedula != $registro['datos']['cc_inspector']) {
                continue;
            }

            $registro['datos']['fechas'] = array_values(
                array_filter($registro['datos']['fechas'], fn ($f) => $f != $fecha)
            );
        }

        unset($registro);
        $this->guardar($historico, 'no_dobles', $lista);
    }

    /** Inspecciones que el inspector hizo ese día, para dejarlas anotadas. */
    private function inspeccionesDelDia($cedula, string $fecha)
    {
        $conteo = TblBitacoraContrato::where('FECHA', $fecha)
            ->where('state', 1)
            ->where('CC_OPERARIO', $cedula)
            ->count();

        return $conteo;
    }

    /**
     * Añade una fecha con su cantidad a una de las listas con inspecciones.
     *
     * Si la fecha ya estaba no se duplica: el usuario puede pulsar dos veces y
     * el segundo clic no debe sumar otra vez.
     */
    private function anadirInspeccion(TblProduccionHistorico $historico, string $columna, $cedula, string $fecha, $cantidad): void
    {
        $lista = $this->leer($historico, $columna);
        $clave = self::LISTAS[$columna];
        $registro = &$this->registroDe($lista, $clave, $cedula, ['totalInspecciones' => []]);

        foreach ($registro['datos']['totalInspecciones'] as $inspeccion) {
            if ($inspeccion['fecha'] == $fecha) {
                unset($registro);
                $this->guardar($historico, $columna, $lista);

                return;
            }
        }

        $registro['datos']['totalInspecciones'][] = ['fecha' => $fecha, 'total_contratos' => $cantidad];

        unset($registro);
        $this->guardar($historico, $columna, $lista);
    }

    /** Quita una fecha de una de las listas con inspecciones. */
    private function quitarInspeccion(TblProduccionHistorico $historico, string $columna, $cedula, string $fecha): void
    {
        $lista = $this->leer($historico, $columna);
        $clave = self::LISTAS[$columna];

        foreach ($lista[$clave] ?? [] as &$registro) {
            if ($cedula != $registro['datos']['cc_inspector']) {
                continue;
            }

            $registro['datos']['totalInspecciones'] = array_values(array_filter(
                $registro['datos']['totalInspecciones'],
                fn ($inspeccion) => $inspeccion['fecha'] != $fecha
            ));
        }

        unset($registro);
        $this->guardar($historico, $columna, $lista);
    }

    /**
     * El registro del inspector dentro de una lista, creándolo si no existe.
     *
     * Se devuelve por referencia para poder escribir dentro sin tener que
     * buscarlo otra vez.
     */
    private function &registroDe(array &$lista, string $clave, $cedula, array $vacio): array
    {
        if (! isset($lista[$clave])) {
            $lista[$clave] = [];
        }

        foreach ($lista[$clave] as &$registro) {
            if ($registro['datos']['cc_inspector'] == $cedula) {
                return $registro;
            }
        }

        unset($registro);

        $lista[$clave][] = ['datos' => ['cc_inspector' => $cedula] + $vacio];
        $nuevo = &$lista[$clave][count($lista[$clave]) - 1];

        return $nuevo;
    }

    private function leer(TblProduccionHistorico $historico, string $columna): array
    {
        return json_decode($historico->{$columna} ?? '', true) ?? [];
    }

    private function guardar(TblProduccionHistorico $historico, string $columna, array $lista): void
    {
        $historico->{$columna} = json_encode($lista);
        $historico->save();
    }
}
