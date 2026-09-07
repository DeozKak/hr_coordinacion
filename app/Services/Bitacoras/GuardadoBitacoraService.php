<?php

namespace App\Services\Bitacoras;

use App\Jobs\CorreoBitacora;
use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Bitacoras\TblBitacoraFallida;
use App\Models\Bitacoras\TblDvInsp;
use App\Models\Bitacoras\TblTempContrato;
use App\Models\Bitacoras\TblTempFallida;
use App\Models\Programacion\TblProgramacionBase;
use App\Models\TblInspCali;
use App\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cierre de una bitácora: el borrador pasa a ser definitivo.
 *
 * La fuente son las tablas de autoguardado, no lo que la pantalla lleve en
 * memoria. Antes los contratos se reconstruían del payload del navegador
 * mientras las fallidas ya salían del borrador, de modo que convivían dos
 * versiones de la misma bitácora y sólo una se había ido guardando sola.
 *
 * El Excel ya no se genera aquí. Se arma cuando alguien lo descarga
 * ({@see ExcelBitacoraService}), así que un fallo suyo no puede dejar la
 * bitácora a medias ni el disco lleno de archivos que nadie abre.
 */
class GuardadoBitacoraService
{
    /** Lo que la pantalla enseña cuando aún no se ha elegido causal. */
    private const SIN_CAUSAL = AutoguardadoService::SIN_CAUSAL;

    /**
     * Guarda la bitácora en curso de una persona y devuelve cuánto quedó.
     *
     * Todo ocurre en una transacción: o queda la bitácora entera o no queda
     * nada. Antes el bloque que la cerraba atrapaba su propia excepción y
     * seguía adelante, con lo que un fallo ahí dejaba media bitácora escrita.
     *
     * @return array{bitacora: int, contratos: int, devoluciones: int, fallidas: int}
     */
    public function guardar(TblBitacoraArchivo $bitacora, User $supervisor): array
    {
        $borrador = TblTempContrato::where('id_bitacora', $bitacora->id)->get();

        $this->exigirCausalEnLasDevoluciones($borrador);

        return DB::transaction(function () use ($bitacora, $supervisor, $borrador) {
            $contratos = 0;
            $devoluciones = 0;

            foreach ($borrador as $fila) {
                if ($this->esDevolucion($fila)) {
                    $devoluciones += $this->guardarDevolucion($fila, $bitacora, $supervisor) ? 1 : 0;

                    continue;
                }

                $contratos += $this->guardarContrato($fila, $bitacora) ? 1 : 0;
            }

            $fallidas = $this->volcarFallidas($bitacora);

            $bitacora->finished = 1;
            $bitacora->save();

            TblTempContrato::where('id_bitacora', $bitacora->id)->delete();

            return [
                'bitacora' => $bitacora->id,
                'contratos' => $contratos,
                'devoluciones' => $devoluciones,
                'fallidas' => $fallidas,
            ];
        });
    }

    /**
     * Descarta la bitácora en curso y su borrador.
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
     * El aviso por correo va fuera de la transacción y no la compromete: que
     * el envío falle no es motivo para perder la bitácora.
     */
    public function avisar(int $idBitacora, User $usuario): void
    {
        try {
            CorreoBitacora::dispatch($idBitacora, $usuario);
        } catch (\Throwable $e) {
            Log::error($e);
        }
    }

    private function esDevolucion(TblTempContrato $fila): bool
    {
        return $fila->ESTADO === 'DV';
    }

    /**
     * Una devolución sin causal no se puede guardar, y se comprueba antes de
     * abrir la transacción: si se dejara para el recorrido, la mitad de la
     * bitácora ya estaría escrita cuando saltara el aviso.
     */
    private function exigirCausalEnLasDevoluciones(Collection $borrador): void
    {
        $sinCausal = $borrador->first(
            fn (TblTempContrato $f) => $this->esDevolucion($f)
                && (blank($f->CAUSAL) || $f->CAUSAL === self::SIN_CAUSAL)
        );

        if ($sinCausal) {
            throw new RuntimeException(
                'Por favor, seleccione una causal para los contratos en estado de devolución.'
            );
        }
    }

    /**
     * @return bool si de verdad se insertó; una repetida se omite en silencio
     */
    private function guardarContrato(TblTempContrato $fila, TblBitacoraArchivo $bitacora): bool
    {
        if ($this->contratoRepetido($fila)) {
            return false;
        }

        $contrato = new TblBitacoraContrato;
        $contrato->CC_OPERARIO = $fila->CC_OPERARIO;
        $contrato->MUNICIPIO = $fila->MUNICIPIO;
        $contrato->FECHA = $fila->FECHA;
        $contrato->No_ACTA = $fila->No_ACTA;
        $contrato->TIPO_TRABAJO = $fila->TIPO_TRABAJO;
        $contrato->CONTRATO = $fila->CONTRATO;
        $contrato->ORDEN_TRABAJO = $fila->ORDEN_TRABAJO;
        $contrato->ORDEN_EXT = $fila->ORDEN_EXT;
        $contrato->CATEGORIA = $this->categoriaDe($fila);
        $contrato->RESULTADO_CIERRE = $fila->RESULTADO_CIERRE;
        $contrato->HORA_INICIO = $fila->HORA_INICIO;
        $contrato->HORA_FINAL = $fila->HORA_FINAL;
        $contrato->DURACION_INSP = $this->duracion($fila->HORA_INICIO, $fila->HORA_FINAL);
        $contrato->PRIORIDAD = 'Sin prioridad';
        $contrato->setAttribute('4_RECINTOS', $fila->getAttribute('4_RECINTOS'));
        $contrato->vence = $fila->vence;
        $contrato->PERIODO_GRACIA = $fila->PERIODO_GRACIA;
        $contrato->CAUSAL_RECHAZO = $fila->CAUSAL_RECHAZO;
        $contrato->id_bitacora = $bitacora->id;
        $contrato->state = 1;
        $contrato->save();

        $this->marcarDevolucionesGestionadas($fila);

        return true;
    }

    /**
     * Las inspecciones de saneamiento se repiten legítimamente sobre el mismo
     * contrato y orden, así que para ellas el acta distingue.
     */
    private function contratoRepetido(TblTempContrato $fila): bool
    {
        $consulta = TblBitacoraContrato::where('CONTRATO', $fila->CONTRATO)
            ->where('ORDEN_TRABAJO', $fila->ORDEN_TRABAJO);

        if (in_array($fila->TIPO_TRABAJO, ['SA 12164', 'SA 12163'], true)) {
            return $consulta->where('No_ACTA', $fila->No_ACTA)->exists();
        }

        return $consulta->where('TIPO_TRABAJO', $fila->TIPO_TRABAJO)->exists();
    }

    /**
     * Cerrar bien un contrato salda la devolución que tuviera pendiente.
     */
    private function marcarDevolucionesGestionadas(TblTempContrato $fila): void
    {
        TblDvInsp::where('CONTRATO', $fila->CONTRATO)
            ->where('ORDEN_TRABAJO', $fila->ORDEN_TRABAJO)
            ->whereNull('FECHA_GESTION')
            ->update([
                'GESTIONADO' => 1,
                'FECHA_GESTION' => date('Y-m-d'),
            ]);
    }

    /**
     * @return bool si de verdad se insertó; una ya devuelta se omite
     */
    private function guardarDevolucion(TblTempContrato $fila, TblBitacoraArchivo $bitacora, User $supervisor): bool
    {
        $yaDevuelto = TblDvInsp::where('CONTRATO', $fila->CONTRATO)
            ->where('ORDEN_TRABAJO', $fila->ORDEN_TRABAJO)
            ->exists();

        if ($yaDevuelto) {
            return false;
        }

        $dv = new TblDvInsp;
        $dv->SUPERVISOR = $supervisor->id;
        $dv->INSPECTOR = $this->idInspector($fila->CC_OPERARIO);
        $dv->CC_OPERARIO = $fila->CC_OPERARIO;
        $dv->MUNICIPIO = $fila->MUNICIPIO;
        $dv->FECHA_INSP = $fila->FECHA;
        $dv->No_ACTA = $fila->No_ACTA;
        $dv->TIPO_TRABAJO = $fila->TIPO_TRABAJO;
        $dv->CONTRATO = $fila->CONTRATO;
        $dv->ORDEN_TRABAJO = $fila->ORDEN_TRABAJO;
        $dv->ORDEN_EXT = $fila->ORDEN_EXT;
        $dv->CATEGORIA = $this->categoriaDe($fila);
        $dv->RESULTADO_CIERRE = $fila->RESULTADO_CIERRE;
        $dv->HORA_INICIO = $fila->HORA_INICIO;
        $dv->HORA_FINAL = $fila->HORA_FINAL;
        $dv->DURACION_INSP = $this->duracion($fila->HORA_INICIO, $fila->HORA_FINAL);
        $dv->setAttribute('4_RECINTOS', $fila->getAttribute('4_RECINTOS'));
        $dv->CAUSAL = $fila->CAUSAL;
        $dv->vence = $fila->vence;
        $dv->FECHA_DV = date('Y-m-d');
        $dv->GESTIONADO = 0;
        $dv->DIAS_SIN_GESTION = 0;
        $dv->id_bitacora = $bitacora->id;
        $dv->ACTIVADO = 1;
        $dv->save();

        return true;
    }

    /**
     * Las fallidas ya vivían en el borrador; se trasladan de un golpe con una
     * subconsulta en lugar de fila a fila.
     */
    private function volcarFallidas(TblBitacoraArchivo $bitacora): int
    {
        $pendientes = TblTempFallida::where('id_bitacora', $bitacora->id);
        $cuantas = (clone $pendientes)->count();

        if ($cuantas === 0) {
            return 0;
        }

        TblBitacoraFallida::insertUsing([
            'NOMBRE', 'id', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA', 'No_ACTA', 'TIPO_TRABAJO',
            'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT', 'CATEGORIA', 'RESULTADO_CIERRE',
            'created_at', 'updated_at', 'id_bitacora', 'id_usuario', 'id_super',
        ], $pendientes);

        $pendientes->delete();

        return $cuantas;
    }

    /**
     * Si el Excel de origen no traía categoría, se toma de la base de
     * programación por el número de contrato.
     */
    private function categoriaDe(TblTempContrato $fila): ?string
    {
        if (filled($fila->CATEGORIA)) {
            return $fila->CATEGORIA;
        }

        $contrato = str_replace(':', '', (string) $fila->CONTRATO);

        return TblProgramacionBase::where('CONTRATO', $contrato)->value('NOM_CATE');
    }

    /**
     * Duración de la inspección en HH:MM.
     *
     * Una hora final menor que la inicial significa que la inspección cruzó la
     * medianoche, no que esté al revés.
     */
    private function duracion(?string $inicio, ?string $fin): ?string
    {
        if (blank($inicio) || blank($fin)) {
            return null;
        }

        try {
            $desde = new DateTime($inicio);
            $hasta = new DateTime($fin);
        } catch (\Exception $e) {
            return null;
        }

        if ($hasta < $desde) {
            $hasta->modify('+1 day');
        }

        return $desde->diff($hasta)->format('%H:%I');
    }

    private function idInspector(?string $cedula): ?int
    {
        return TblInspCali::where('cedula', $cedula)->value('id');
    }
}
