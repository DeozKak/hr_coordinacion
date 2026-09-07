<?php

namespace App\Services\Bitacoras;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblTempContrato;
use App\Models\Bitacoras\TblTempFallida;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Borrador de una bitácora en curso.
 *
 * Cada cambio que hace quien la diligencia se escribe aquí, en
 * `tbl_temp_contratos`, y es esta tabla —no lo que la pantalla lleve en
 * memoria— la que se convierte en la bitácora definitiva al guardar. Eso
 * significa que un cambio que no llegue a esta tabla se pierde, y de ahí que
 * la pantalla ya no dé por buena una edición hasta que el servidor la confirme.
 */
class AutoguardadoService
{
    /**
     * Columnas que la pantalla deja editar.
     *
     * El controlador hacía `$contrato->$campo = $valor` con el nombre llegando
     * del navegador, así que alcanzaba cualquier columna de la tabla: el
     * contrato, la cédula del operario o el `id_bitacora`, que lo movería al
     * borrador de otra persona. Son las tres del formulario: la cantidad de
     * recintos, el estado y, cuando el estado es DV, la causal.
     */
    public const CAMPOS_EDITABLES = ['4_RECINTOS', 'ESTADO', 'CAUSAL'];

    /** Estados posibles de una fila: se queda en la bitácora, o se devuelve. */
    public const ESTADOS = ['OK', 'DV'];

    /**
     * Valor de «sin causal». No es null: la columna es NOT NULL y trae ese
     * mismo texto por omisión, que es además el que la pantalla enseña.
     */
    public const SIN_CAUSAL = '--SELECCIONE CAUSAL--';

    /**
     * La bitácora en borrador de quien la abrió, lista para tocarla.
     *
     * Ni restaurar ni descartar comprobaban nada: bastaba con tener el permiso
     * `generar_bitacoras` y el identificador para abrir —o eliminar— el
     * borrador de cualquier otra persona. Y descartar tampoco miraba si ya
     * estaba cerrada, con lo que se podía borrar el registro de un reporte
     * terminado y dejar sus contratos huérfanos en `tbl_bitacora_contratos`.
     */
    public function borradorPropio(int $idBitacora): TblBitacoraArchivo
    {
        $bitacora = TblBitacoraArchivo::find($idBitacora);

        if (! $bitacora) {
            abort(404, 'La bitácora no existe.');
        }

        if ((int) $bitacora->id_usuario !== Auth::id()) {
            abort(403, 'Esa bitácora es de otra persona.');
        }

        /* DomainException y no RuntimeException a propósito: el `abort(403)`
           de arriba lanza un HttpException, que extiende RuntimeException, así
           que un `catch (RuntimeException)` en el controlador se tragaría el
           403 y lo devolvería como 422. */
        if ((int) $bitacora->finished === 1) {
            throw new DomainException('La bitácora ya está cerrada; no se puede modificar.');
        }

        return $bitacora;
    }

    /**
     * Descarta el borrador entero: sus filas y el archivo que lo agrupa.
     */
    public function descartar(TblBitacoraArchivo $bitacora): void
    {
        DB::transaction(function () use ($bitacora) {
            TblTempContrato::where('id_bitacora', $bitacora->id)->delete();
            TblTempFallida::where('id_bitacora', $bitacora->id)->delete();
            $bitacora->delete();
        });
    }

    /**
     * Guarda un cambio y devuelve el valor tal y como quedó almacenado.
     *
     * Se devuelve lo persistido, no lo recibido: es lo que permite a la
     * pantalla pintar el valor bueno en lugar de confiar en el suyo.
     */
    public function actualizarCampo(int $id, string $campo, mixed $valor): array
    {
        if (! in_array($campo, self::CAMPOS_EDITABLES, true)) {
            throw new RuntimeException("El campo {$campo} no se puede editar.");
        }

        $fila = TblTempContrato::find($id);

        if (! $fila) {
            throw new RuntimeException('La fila ya no existe en el borrador.');
        }

        if (! $this->esDeQuienEdita($fila)) {
            throw new RuntimeException('Esa fila pertenece al borrador de otra persona.');
        }

        if ($campo === 'ESTADO' && ! in_array($valor, self::ESTADOS, true)) {
            throw new RuntimeException('El estado sólo puede ser OK o DV.');
        }

        $fila->{$campo} = $valor;

        /* Volver a OK descarta la causal, igual que hace la pantalla: si no, una
           fila que dejó de ser devolución conservaría su motivo en la tabla. */
        if ($campo === 'ESTADO' && $valor === 'OK') {
            $fila->CAUSAL = self::SIN_CAUSAL;
        }

        $fila->save();

        return [
            'id' => $fila->id,
            'campo' => $campo,
            'valor' => $fila->{$campo},
            'causal' => $fila->CAUSAL,
        ];
    }

    /**
     * Añade una inspección hecha en papel al borrador.
     */
    public function agregarInspeccion(array $datos): TblTempContrato
    {
        $fila = new TblTempContrato;

        $fila->NOMBRE = $datos['nombre'];
        $fila->CC_OPERARIO = $datos['cedula'];
        $fila->MUNICIPIO = $datos['municipio'];
        $fila->FECHA = $datos['fecha'];
        $fila->No_ACTA = $datos['acta'];
        $fila->TIPO_TRABAJO = $datos['tipoTrabajo'];
        $fila->CONTRATO = $datos['contrato'];
        $fila->CATEGORIA = $datos['categoria'];
        $fila->setAttribute('4_RECINTOS', $datos['cantidadRecintos'] ?? 'NO');
        $fila->RESULTADO_CIERRE = $datos['resultadoCierre'];
        $fila->CAUSAL_RECHAZO = $datos['rechazo'] ?? null;
        $fila->id_bitacora = $datos['id_bitacora'];
        $fila->id_super = $datos['id_super'];
        $fila->id_usuario = Auth::id();

        /* Explícito, aunque la columna ya traiga 'OK' por omisión: quien lea
           esto no debería tener que ir al esquema para saber con qué estado
           nace una fila. */
        $fila->ESTADO = 'OK';

        $fila->save();

        return $fila;
    }

    /**
     * El borrador es de quien lo abrió; nadie edita el de otra persona.
     */
    private function esDeQuienEdita(TblTempContrato $fila): bool
    {
        return $fila->id_usuario === null || $fila->id_usuario === Auth::id();
    }
}
