<?php

namespace App\Services\Produccion;

use App\Models\Produccion\TblProduccionCorte;
use App\Models\Produccion\TblProduccionHistorico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Alta y edición de los cortes de producción.
 *
 * Un corte no puede pisar el rango de otro: los contratos de un día tienen que
 * caer en un corte y solo en uno, o la producción se cuenta dos veces.
 */
class CorteService
{
    private const REGLAS = [
        'nombre'       => 'required|string|max:255',
        'fecha_inicio' => 'required|date',
        'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        'meta'         => 'required|integer|max:250',
        'dobles'       => 'required|integer|max:50',
    ];

    private const MENSAJES = [
        'nombre.required'          => 'Llene por favor el campo nombre',
        'nombre.string'            => 'El nombre debe ser una cadena de texto válida',
        'nombre.max'               => 'El nombre no debe superar los 255 caracteres',
        'fecha_inicio.required'    => 'Debe seleccionar una fecha de inicio',
        'fecha_inicio.date'        => 'La fecha de inicio debe ser una fecha válida',
        'fecha_fin.required'       => 'Debe seleccionar una fecha de finalización',
        'fecha_fin.date'           => 'La fecha de finalización debe ser una fecha válida',
        'fecha_fin.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio',
        'meta.required'            => 'Debe ingresar la meta',
        'meta.integer'             => 'La meta debe ser un número entero',
        'meta.max'                 => 'La meta no puede ser mayor a 250',
        'dobles.required'          => 'Debe ingresar la cantidad de dobles',
        'dobles.integer'           => 'La cantidad de dobles debe ser un número entero',
        'dobles.max'               => 'La cantidad de dobles no puede superar 50',
    ];

    /**
     * @return array{estado?: int, datos: array}
     */
    public function crear(array $entrada): array
    {
        $validador = Validator::make($entrada, self::REGLAS, self::MENSAJES);

        if ($validador->fails()) {
            return ['estado' => 422, 'datos' => ['error' => $validador->errors()->first()]];
        }

        if ($this->seSolapa($entrada['fecha_inicio'], $entrada['fecha_fin'])) {
            return ['estado' => 422, 'datos' => ['error' => 'El rango de fechas se solapa con otro corte existente.']];
        }

        /* El corte y su histórico se crean juntos: sin histórico la pantalla de
           detalle no tiene dónde guardar los ajustes y revienta al abrirla. */
        /* Se asigna campo a campo y no con create(): estos dos modelos no
           declaran $fillable, así que la asignación masiva está cerrada. */
        $corte = DB::transaction(function () use ($entrada) {
            $corte = new TblProduccionCorte();
            $corte->nombre = $entrada['nombre'];
            $corte->fecha_inicio = $entrada['fecha_inicio'];
            $corte->fecha_fin = $entrada['fecha_fin'];
            $corte->meta = $entrada['meta'];
            $corte->dobles = $entrada['dobles'];
            $corte->save();

            $historico = new TblProduccionHistorico();
            $historico->id_corte = $corte->id;
            $historico->save();

            return $corte;
        });

        return ['datos' => ['success' => $corte]];
    }

    /**
     * Edición desde la pantalla de cortes.
     *
     * Devuelve los mismos `status` de texto que ya interpreta el JavaScript.
     */
    public function actualizar($id, array $entrada): array
    {
        $corte = TblProduccionCorte::find($id);

        if ($corte === null) {
            return ['estado' => 404, 'datos' => ['error' => 'El corte no existe.']];
        }

        $corte->nombre = $entrada['nombre'];
        $corte->fecha_inicio = $entrada['fecha_inicio'];
        $corte->fecha_fin = $entrada['fecha_fin'];
        $corte->meta = $entrada['meta'];
        $corte->dobles = $entrada['dobles'];

        if ($corte->fecha_inicio === $corte->fecha_fin) {
            return ['datos' => [
                'status'  => 'fechas_iguales',
                'message' => 'La fecha de inicio no puede ser igual a la fecha de fin.',
            ]];
        }

        if ($corte->fecha_inicio > $corte->fecha_fin) {
            return ['datos' => [
                'status'  => 'fechaMayor',
                'message' => 'La fecha de inicio no puede ser mayor a la fecha de fin.',
            ]];
        }

        $solape = $this->seSolapa($corte->fecha_inicio, $corte->fecha_fin, $corte->id);

        if ($solape) {
            return ['datos' => [
                'status'  => 'error',
                'message' => 'El rango de fechas se solapa con un corte existente. solapamiento: '
                    . $solape->nombre . ' ' . $solape->fecha_inicio . ' ' . $solape->fecha_fin,
            ]];
        }

        $corte->save();

        return ['datos' => ['success' => $corte]];
    }

    /** El corte con el que se pisa el rango, si lo hay. */
    private function seSolapa($inicio, $fin, $excluir = null): ?TblProduccionCorte
    {
        $consulta = TblProduccionCorte::where(function ($q) use ($inicio, $fin) {
            $q->whereBetween('fecha_inicio', [$inicio, $fin])
                ->orWhereBetween('fecha_fin', [$inicio, $fin])
                ->orWhere(function ($interior) use ($inicio, $fin) {
                    $interior->where('fecha_inicio', '<', $inicio)
                        ->where('fecha_fin', '>', $fin);
                });
        });

        if ($excluir !== null) {
            $consulta->where('id', '!=', $excluir);
        }

        return $consulta->first();
    }
}
