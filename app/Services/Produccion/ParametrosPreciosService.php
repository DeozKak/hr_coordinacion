<?php

namespace App\Services\Produccion;

use App\Models\Nomina\TblParametroPrecios;

/**
 * Precios por zona y categoría, vigentes en un rango de fechas.
 *
 * Los códigos de respuesta son los que la vista ya interpreta y no se pueden
 * renumerar sin tocar el JavaScript:
 *
 *   1 faltan fechas   2 fechas invertidas   3 datos no numéricos
 *   4 se solapa       5 guardado            6 error al guardar
 *   7 no hubo cambios (solo al actualizar)
 */
class ParametrosPreciosService
{
    /** Las columnas de precio, en el orden en que llegan del formulario. */
    private const PRECIOS = [
        'res_metro'             => 'metroRes',
        'res_norte'             => 'norteRes',
        'res_cauca'             => 'caucaRes',
        'com_metro'             => 'metroCom',
        'com_norte'             => 'norteCom',
        'com_cauca'             => 'caucaCom',
        'inspeccion_industrial' => 'inspeccionInd',
    ];

    /** Los precios vigentes para un mes, o todo en cero si no hay ninguno. */
    public function vigentesEn(string $desde, string $hasta): array
    {
        $parametros = TblParametroPrecios::where('fecha_inicio', '<=', $hasta)
            ->where('fecha_fin', '>=', $desde)
            ->first();

        return [$this->comoArreglo($parametros)];
    }

    /** Los precios de un año, buscados por el año de las dos fechas. */
    public function vigentesEnElAno(string $anio): array
    {
        $parametros = TblParametroPrecios::where('fecha_inicio', 'like', $anio . '%')
            ->where('fecha_fin', 'like', $anio . '%')
            ->first();

        return [$this->comoArreglo($parametros)];
    }

    public function crear(array $entrada): array
    {
        if ($error = $this->revisar($entrada)) {
            return $error;
        }

        if ($solape = $this->solape($entrada['fechaPrecioInicio'], $entrada['fechaPrecioFin'])) {
            return $solape;
        }

        $creado = TblParametroPrecios::create($this->columnas($entrada));

        return ['status' => $creado ? 5 : 6];
    }

    public function actualizar($id, array $entrada): array
    {
        if ($error = $this->revisar($entrada)) {
            return $error;
        }

        if ($solape = $this->solape($entrada['fechaPrecioInicio'], $entrada['fechaPrecioFin'], $id)) {
            return $solape;
        }

        $actual = TblParametroPrecios::find($id);
        $nuevas = $this->columnas($entrada);

        // Guardar lo mismo que ya había se avisa en vez de contarlo como éxito.
        if ($actual && $this->sinCambios($actual, $nuevas)) {
            return ['status' => 7];
        }

        return ['status' => TblParametroPrecios::where('id', $id)->update($nuevas) ? 5 : 6];
    }

    /** Fechas presentes, en orden, y precios numéricos. */
    private function revisar(array $entrada): ?array
    {
        $inicio = $entrada['fechaPrecioInicio'] ?? '';
        $fin = $entrada['fechaPrecioFin'] ?? '';

        if ($inicio === '' || $fin === '' || $inicio === null || $fin === null) {
            return ['status' => 1];
        }

        if ($fin < $inicio) {
            return ['status' => 2];
        }

        foreach (self::PRECIOS as $campo) {
            if (! is_numeric(intval($entrada[$campo] ?? null))) {
                return ['status' => 3];
            }
        }

        return null;
    }

    /** ¿El rango pisa el de otro registro? */
    private function solape(string $inicio, string $fin, $excluir = null): ?array
    {
        $consulta = TblParametroPrecios::where('fecha_inicio', '<=', $fin)
            ->where('fecha_fin', '>=', $inicio);

        if ($excluir !== null) {
            $consulta->where('id', '!=', $excluir);
        }

        $existente = $consulta->first();

        return $existente === null ? null : [
            'status'       => 4,
            'id'           => $existente->id,
            'fecha_inicio' => $existente->fecha_inicio,
            'fecha_fin'    => $existente->fecha_fin,
        ];
    }

    /** @return array<string, mixed> */
    private function columnas(array $entrada): array
    {
        $columnas = [
            'fecha_inicio' => $entrada['fechaPrecioInicio'],
            'fecha_fin'    => $entrada['fechaPrecioFin'],
        ];

        foreach (self::PRECIOS as $columna => $campo) {
            $columnas[$columna] = intval($entrada[$campo] ?? 0);
        }

        return $columnas;
    }

    private function sinCambios(TblParametroPrecios $actual, array $nuevas): bool
    {
        foreach ($nuevas as $columna => $valor) {
            if ($actual->{$columna} != $valor) {
                return false;
            }
        }

        return true;
    }

    /** Un registro de precios como lo espera la vista, o todo en cero. */
    private function comoArreglo(?TblParametroPrecios $parametros): array
    {
        $arreglo = [];

        foreach (array_keys(self::PRECIOS) as $columna) {
            $arreglo[$columna] = $parametros?->{$columna} ?? 0;
        }

        return $arreglo;
    }
}
