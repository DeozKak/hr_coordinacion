<?php

namespace App\Services\Produccion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Los tres catálogos de la pantalla de configuración: sedes, zonas y causales.
 *
 * Los tres se dan de alta, se renombran y se activan igual; lo único que cambia
 * es la tabla, la columna del nombre y cómo se redacta el mensaje. Antes eran
 * tres bloques calcados de ciento y pico líneas cada uno.
 *
 * Los textos van escritos y no derivados: en español el género y el artículo no
 * salen de una regla —"la sede" pero "el causal"—, y componerlos a mano da
 * frases raras.
 */
class CatalogoProduccionService
{
    /**
     * @param class-string<Model> $modelo
     * @param string $columna Columna que guarda el nombre.
     * @param string $tabla   Tabla, para la regla unique.
     * @param string $clave   Clave con la que la vista recibe el registro.
     * @param array<string, string> $textos Mensajes de este catálogo.
     */
    private function __construct(
        private string $modelo,
        private string $columna,
        private string $tabla,
        private string $clave,
        private int $maximo,
        private array $textos
    ) {}

    public static function para(string $catalogo): self
    {
        return match ($catalogo) {
            'sede' => new self(
                \App\Models\Zonificacion\TblLocalidadesSede::class,
                'nombre', 'tbl_localidades_sedes', 'sede', 100,
                [
                    'creado'      => 'Sede creada con éxito',
                    'actualizado' => 'Sede actualizada correctamente.',
                    'estado'      => 'Estado de la sede actualizado exitosamente',
                    'noExiste'    => 'La sede no existe.',
                    'noEncontrado' => 'Sede no encontrada',
                    'pedirNombre' => 'Por favor ingrese el nombre de la sede.',
                    'debeSerTexto' => 'El nombre de la sede debe ser un texto.',
                    'muyLargo'    => 'El nombre de la sede no puede superar los 100 caracteres.',
                    'yaExiste'    => 'La sede ya existe.',
                ]
            ),
            'zona' => new self(
                \App\Models\Produccion\TblProduccionZona::class,
                'nombre', 'tbl_produccion_zonas', 'zona', 255,
                [
                    'creado'      => 'Zona creada con éxito',
                    'actualizado' => 'Zona actualizada correctamente.',
                    'estado'      => 'Estado de la zona actualizado exitosamente',
                    'noExiste'    => 'La zona no existe.',
                    'noEncontrado' => 'Zona no encontrada',
                    'pedirNombre' => 'Por favor ingrese el nombre de la zona.',
                    'debeSerTexto' => 'El nombre de la zona debe ser un texto.',
                    'muyLargo'    => 'El nombre de la zona no puede superar los 255 caracteres.',
                    'yaExiste'    => 'La zona ya existe.',
                ]
            ),
            'causal' => new self(
                \App\Models\Bitacoras\TblBitacorasCausal::class,
                'nom_causal', 'tbl_bitacoras_causales', 'causal', 255,
                [
                    'creado'      => 'Causal creado con éxito',
                    'actualizado' => 'Causal actualizado correctamente.',
                    'estado'      => 'Estado del causal actualizado exitosamente',
                    'noExiste'    => 'El causal no existe.',
                    'noEncontrado' => 'Causal no encontrada',
                    'pedirNombre' => 'Por favor ingrese el nombre del causal.',
                    'debeSerTexto' => 'El nombre del causal debe ser un texto.',
                    'muyLargo'    => 'El nombre del causal no puede superar los 255 caracteres.',
                    'yaExiste'    => 'El nombre del causal ya existe.',
                ]
            ),
        };
    }

    public function crear(array $entrada): array
    {
        if ($error = $this->revisar($entrada)) {
            return $error;
        }

        $registro = DB::transaction(function () use ($entrada) {
            $registro = new $this->modelo();
            $registro->{$this->columna} = $entrada[$this->columna];
            $registro->save();

            return $registro;
        });

        return $this->respuesta($this->textos['creado'], $registro);
    }

    public function actualizar($id, array $entrada): array
    {
        if ($error = $this->revisar($entrada, $id)) {
            return $error;
        }

        $registro = $this->modelo::find($id);

        if ($registro === null) {
            return ['estado' => 404, 'datos' => ['error' => $this->textos['noExiste']]];
        }

        DB::transaction(function () use ($registro, $entrada) {
            $registro->{$this->columna} = $entrada[$this->columna];
            $registro->save();
        });

        return $this->respuesta($this->textos['actualizado'], $registro);
    }

    public function buscar($id): ?Model
    {
        return $this->modelo::find($id);
    }

    /** Activa o desactiva el registro. */
    public function alternarEstado($id): array
    {
        $registro = $this->modelo::find($id);

        if ($registro === null) {
            return ['estado' => 404, 'datos' => ['error' => $this->textos['noEncontrado']]];
        }

        $registro->status = ! $registro->status;
        $registro->save();

        return $this->respuesta($this->textos['estado'], $registro);
    }

    /**
     * Nombre presente, con largo válido y sin repetir.
     *
     * @param mixed $id Al editar, el registro que no cuenta como duplicado.
     */
    private function revisar(array $entrada, $id = null): ?array
    {
        $unica = 'unique:' . $this->tabla . ',' . $this->columna . ($id === null ? '' : ',' . $id);

        $validador = Validator::make($entrada, [
            $this->columna => 'required|string|max:' . $this->maximo . '|' . $unica,
        ], [
            $this->columna . '.required' => $this->textos['pedirNombre'],
            $this->columna . '.string'   => $this->textos['debeSerTexto'],
            $this->columna . '.max'      => $this->textos['muyLargo'],
            $this->columna . '.unique'   => $this->textos['yaExiste'],
        ]);

        return $validador->fails()
            ? ['estado' => 422, 'datos' => ['error' => $validador->errors()->first()]]
            : null;
    }

    private function respuesta(string $mensaje, Model $registro): array
    {
        return ['datos' => ['success' => $mensaje, $this->clave => $registro]];
    }
}
