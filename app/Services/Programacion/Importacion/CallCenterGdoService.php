<?php

namespace App\Services\Programacion\Importacion;

use App\Jobs\ProcessCallCenterGdo;
use App\Models\Programacion\TblProgramacionUsuario;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Recepción del archivo del call center.
 *
 * Aquí no se procesa nada: el archivo lo interpreta un trabajo en segundo
 * plano. Lo que se hace es abrir la programación que va a recibir el
 * resultado y dejar el archivo donde el trabajo pueda leerlo.
 */
class CallCenterGdoService
{
    /**
     * Estado de una programación que aún está siendo procesada.
     *
     * Ni abierta (0) ni terminada (1): el 2 la deja fuera de los listados
     * mientras el trabajo la rellena.
     */
    private const EN_PROCESO = 2;

    /** Dónde se deja el archivo para que lo lea el trabajo. */
    private const CARPETA = 'excel-imports-gdo';

    public function __construct(
        private LectorDeCabeceras $cabeceras
    ) {}

    /** ¿La hoja tiene el formato de la base de GDO? */
    public function formatoCorrecto(Worksheet $hoja): bool
    {
        $formato = Formatos::gdo();

        return $formato->coincide($this->cabeceras->deLaHoja($hoja, $formato));
    }

    /**
     * Abre la programación y encola el procesamiento.
     *
     * Las dos cosas van juntas en una transacción: encolar un trabajo que
     * apunta a una programación que no llegó a guardarse dejaría el proceso
     * fallando en segundo plano sin que nadie se entere.
     */
    public function encolar(UploadedFile $archivo): TblProgramacionUsuario
    {
        return DB::transaction(function () use ($archivo) {
            $programacion = new TblProgramacionUsuario();
            $programacion->nombre = 'Programación GDO ' . Carbon::now()->format('Y-m-d');
            $programacion->id_usuario = Auth::id();
            $programacion->finished = self::EN_PROCESO;
            $programacion->mensaje = 1;
            $programacion->save();

            ProcessCallCenterGdo::dispatch(
                $archivo->store(self::CARPETA),
                $programacion->id,
                Auth::id()
            );

            return $programacion;
        });
    }
}
