<?php

namespace App\Services\Programacion\Importacion;

use App\Models\Programacion\tbl_programacion_base;
use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\Programacion\tbl_programacion_usuario;
use App\Models\tbl_insp_cali;
use App\Services\ProgramacionService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date as FechaExcel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Volcado de la programación masiva de técnicos.
 *
 * El archivo lo genera movilidad con las visitas que ya trae agendadas cada
 * inspector. Todo entra en una sola programación del día, marcada como
 * terminada: no se edita después, sólo se consulta.
 */
class ProgramacionMasivaService
{
    /** Lo que la programación masiva da por sentado en cada fila. */
    private const ACTIVA = 'Si';
    private const SUSPENDIDO = 'No';
    private const ORIGEN = 'TECNICO MOVILIDAD';
    private const HORA_INICIO = '06:59:00 a.m.';
    private const HORA_FINAL = '11:59:00 a.m.';

    /** A quién se le apuntan las visitas que no se pueden atribuir. */
    private const OFICINA = '100. OFICINA';

    /** Columna del Excel => campo del contrato, para lo que es copia directa. */
    private const DIRECTAS = [
        'D' => 'TIPO_TRABAJO',
        'K' => 'CELULAR',
        'J' => 'NOMBRE_USUARIO',
        'H' => 'DIRECCION',
        'G' => 'BARRIO',
        'I' => 'CIUDAD',
        'C' => 'CATEGORIA',
    ];

    public function __construct(
        private ProgramacionService $ejecutados
    ) {}

    /**
     * Inserta la hoja entera.
     *
     * @return true|string true si fue bien; si no, el mensaje para el usuario
     *                     con la fila y la columna que falló.
     */
    public function cargar(Worksheet $hoja): true|string
    {
        try {
            DB::beginTransaction();

            $tabla = $this->abrirProgramacion();

            foreach ($hoja->getRowIterator(2) as $fila) {
                $n = $fila->getRowIndex();

                if ($this->filaIncompleta($hoja, $n)) {
                    continue;
                }

                $contrato = $this->armarContrato($hoja, $n, $tabla->id);

                // armarContrato devuelve el mensaje de error si no pudo.
                if (is_string($contrato)) {
                    DB::rollBack();

                    return $contrato;
                }

                // Lo que ya se ejecutó no se vuelve a programar.
                if ($this->ejecutados->findExecuted(
                    $contrato->CONTRATO, $contrato->TIPO_TRABAJO, $contrato->ORDEN_TRABAJO
                )) {
                    continue;
                }

                $this->retirarProgramacionFutura($contrato->CONTRATO);

                $contrato->save();
            }

            DB::commit();

            return true;
        } catch (\Throwable $e) {
            /* Throwable y no Exception por lo mismo: un Error dejaba la
               transacción abierta y con ella los bloqueos, hasta que la
               conexión moría por tiempo. */
            DB::rollBack();
            Log::error('Error al insertar datos: ' . $e->getMessage());

            return 'Error al insertar datos: ' . $e->getMessage();
        }
    }

    /** La programación del día donde entra todo el archivo. */
    private function abrirProgramacion(): tbl_programacion_usuario
    {
        $tabla = new tbl_programacion_usuario();
        $tabla->nombre = 'Programación tecnicos ' . Carbon::now()->format('Y-m-d');
        $tabla->id_usuario = Auth::id();
        $tabla->finished = 1;   // no se edita: nace cerrada
        $tabla->mensaje = 1;
        $tabla->save();

        return $tabla;
    }

    /**
     * Filas que no sirven: sin fecha de agendamiento, sin tipo o sin orden.
     *
     * El archivo trae las visitas de todo el mes y muchas vienen sin agendar.
     */
    private function filaIncompleta(Worksheet $hoja, int $n): bool
    {
        $agendamiento = $hoja->getCell('N' . $n)->getValue();

        return $agendamiento === '' || $agendamiento === null
            || $hoja->getCell('D' . $n)->getValue() === null
            || $hoja->getCell('S' . $n)->getValue() === null;
    }

    /**
     * Arma el contrato de una fila.
     *
     * @return tbl_programacion_contrato|string El contrato, o el mensaje de error.
     */
    private function armarContrato(Worksheet $hoja, int $n, $idProgramacion): tbl_programacion_contrato|string
    {
        $celda = fn (string $col) => $hoja->getCell($col . $n)->getValue();

        $c = new tbl_programacion_contrato();
        $c->ACTIVA = self::ACTIVA;
        $c->SUSPENDIDO = self::SUSPENDIDO;
        $c->PORQUE_PROGRAMO = self::ORIGEN;
        $c->id_programacion = $idProgramacion;
        $c->mensaje = 1;
        $c->HORA_INICIO = self::HORA_INICIO;
        $c->HORA_FINAL = self::HORA_FINAL;

        $c->JORNADA = $this->jornada((string) $celda('O'));

        foreach (self::DIRECTAS as $columna => $campo) {
            $c->$campo = $celda($columna);
        }

        // El archivo trae el contrato con dos puntos delante; la tabla no.
        $c->CONTRATO = str_replace(':', '', (string) $celda('F'));

        /* Se atrapa Throwable y no Exception: cuando la celda trae texto donde
           debería ir un número, excelToDateTimeObject lanza un TypeError, y
           TypeError extiende Error, no Exception. Con el catch de antes se
           escapaba y el usuario veía la página de error en vez del mensaje
           que dice la fila y la columna. */
        try {
            $c->FECHA = $this->deSerialExcel($celda('E'));
        } catch (\Throwable $e) {
            Log::error($e);

            return 'Error al convertir fecha. revise columna E Fila ' . $n;
        }

        try {
            $c->FECHA_AGENDAMIENTO = $this->deTextoCorto((string) $celda('N'));
        } catch (\Throwable $e) {
            Log::error($e);

            return 'Error al convertir fecha. revise columna N Fila ' . $n;
        }

        $c->ORDEN_TRABAJO = $this->orden($celda('T'), $celda('S'));
        $c->OBSERVACIONES = 'JORNADA: ' . $celda('O') . ' OBSERVACIONES: ' . $celda('P');
        $c->TECNICO = $this->tecnico($hoja, $n, (string) $celda('B'));

        return $c;
    }

    /** La jornada sale del texto libre de la columna O. */
    private function jornada(string $texto): string
    {
        if (str_contains($texto, 'MAÑANA')) {
            return 'mañana';
        }

        if (str_contains($texto, 'TARDE')) {
            return 'tarde';
        }

        // "TRANSCURSO DEL DIA" y cualquier otra cosa cuentan como el día entero.
        return 'todo el dia';
    }

    /**
     * La orden externa manda sobre la masiva cuando viene informada.
     *
     * La comparación se conserva tal cual estaba: con "" o null las dos partes
     * dan falso y se cae en la masiva, que es lo que se quiere.
     */
    private function orden($externa, $masiva)
    {
        return ($externa <> '' || $externa <> null) ? $externa : $masiva;
    }

    /** Fecha guardada como número de serie de Excel. */
    private function deSerialExcel($valor): string
    {
        return FechaExcel::excelToDateTimeObject(is_null($valor) ? 0 : $valor)->format('Y-m-d');
    }

    /**
     * Fecha escrita como texto "28/08/24".
     *
     * El corte explícito es lo que evita que una celda ilegible acabe guardada
     * como 1970-01-01: createFromFormat devuelve false, PHPToExcel(false)
     * devuelve false y excelToDateTimeObject(false) da la fecha cero, todo sin
     * lanzar nada. La fila entraba con una fecha imposible y desaparecía de
     * cualquier consulta de agendamiento sin que nadie se enterara.
     */
    private function deTextoCorto(string $texto): string
    {
        $fecha = DateTime::createFromFormat('d/m/y', trim($texto));

        if ($fecha === false) {
            throw new \RuntimeException('Fecha ilegible: ' . $texto);
        }

        return FechaExcel::excelToDateTimeObject(FechaExcel::PHPToExcel($fecha))->format('Y-m-d');
    }

    /**
     * A quién se le apunta la visita.
     *
     * Si el inspector de la columna B es aprendiz, la visita no es suya: se
     * busca de quién es la orden en la base. Cuando no se puede averiguar
     * —inspector desconocido, orden que no está en la base— se apunta a la
     * oficina en vez de cortar la carga entera por una fila.
     */
    private function tecnico(Worksheet $hoja, int $n, string $nombre): string
    {
        try {
            $inspector = tbl_insp_cali::whereRaw("CONCAT(apellidos, ' ', nombres) = ?", [$nombre])->first();

            if ($inspector->aprendiz === 0) {
                return $inspector->id . '. ' . $nombre;
            }

            if ($inspector->aprendiz !== 1) {
                return self::OFICINA;
            }

            $externa = $hoja->getCell('T' . $n)->getValue();
            $orden = ($externa <> '' || $externa <> null)
                ? $externa
                : $hoja->getCell('S' . $n)->getValue();

            $base = tbl_programacion_base::where('NUMERO_ORDEN', $orden)->first();

            if (! $base) {
                return self::OFICINA;
            }

            $duenio = tbl_insp_cali::where('id', $base->ID_TECNICO)->first();

            return $duenio->id . '. ' . $duenio->apellidos . ' ' . $duenio->nombres;
        } catch (\Exception $e) {
            Log::error($e);

            return self::OFICINA;
        }
    }

    /**
     * Quita la programación futura que ya tuviera el contrato.
     *
     * La masiva es la que manda: si movilidad reagenda una visita, la anterior
     * sobra. Sólo se tocan las de hoy en adelante; el histórico no se toca.
     */
    private function retirarProgramacionFutura(?string $contrato): void
    {
        tbl_programacion_contrato::where('CONTRATO', $contrato)
            ->where('FECHA_AGENDAMIENTO', '>=', date('Y-m-d'))
            ->first()
            ?->delete();
    }
}
