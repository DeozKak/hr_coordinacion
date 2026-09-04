<?php

namespace App\Services\Programacion;

use App\Jobs\CorreoProgramacion;
use App\Models\Programacion\TblProgramacionContrato;
use App\Models\Programacion\TblProgramacionUsuario;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cierre de una programación.
 *
 * Al darla por terminada se avisa por correo a quien la armó y se marcan sus
 * contratos como ya notificados. Ese aviso se manda una sola vez: la bandera
 * `mensaje` de la programación es la que lo recuerda, por si se vuelve a
 * cerrar después de reabrirla.
 */
class CierreProgramacionService
{
    /**
     * Cierra la programación.
     *
     * @return array Vacío si fue bien; si no, `error` y `estado`.
     */
    public function cerrar($id): array
    {
        DB::beginTransaction();

        try {
            $programacion = TblProgramacionUsuario::find($id);
            $programacion->finished = 1;
            $programacion->save();

            $this->avisarUnaVez($programacion);

            foreach (TblProgramacionContrato::where('id_programacion', $id)->get() as $contrato) {
                if (! $this->hayQueMarcar($contrato)) {
                    continue;
                }

                if (! $this->fechaValida($contrato->FECHA_AGENDAMIENTO)) {
                    /* Se deshace todo, incluida la marca de finalizada: devolver
                       un error y dejar la programación cerrada a medias sería
                       lo peor de los dos mundos. */
                    DB::rollBack();

                    return [
                        'error'  => 'La fecha debe tener el formato correcto (Y-m-d).',
                        'estado' => 422,
                    ];
                }

                $contrato->mensaje = 1;
                $contrato->save();
            }

            DB::commit();

            return [];
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /** El correo a quien armó la programación, sólo la primera vez. */
    private function avisarUnaVez(TblProgramacionUsuario $programacion): void
    {
        if ($programacion->mensaje != 0) {
            return;
        }

        $programacion->mensaje = 1;
        $programacion->save();

        CorreoProgramacion::dispatch(User::find($programacion->id_usuario), $programacion->id);
    }

    /**
     * Se marcan los contratos con teléfono que no estuvieran ya marcados.
     *
     * La bandera nació para no repetir el mensaje al usuario final. Ese envío
     * ya no existe, pero la marca se conserva porque es la que evita repetir
     * trabajo al reabrir y volver a cerrar una programación.
     */
    private function hayQueMarcar(TblProgramacionContrato $contrato): bool
    {
        return $contrato->CELULAR !== null
            && $contrato->CELULAR !== ''
            && $contrato->mensaje != 1;
    }

    /**
     * Fecha de agendamiento utilizable.
     *
     * En la práctica sólo salta cuando viene vacía: la columna es de tipo date
     * y MySQL ya normaliza cualquier fecha que acepte.
     */
    private function fechaValida($fecha): bool
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $fecha)->format('Y-m-d') === $fecha;
        } catch (\Exception $e) {
            return false;
        }
    }
}
