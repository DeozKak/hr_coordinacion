<?php

namespace App\Services\Programacion;

use App\Models\AsignadasQuejas;
use App\Models\Programacion\TblProgramacionBase;
use App\Models\Programacion\TblProgramacionContrato;
use App\Models\TblInspCali;
use App\Services\ProgramacionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Contratos dentro de una programación: alta, edición y baja.
 *
 * Las transacciones se abren con DB::transaction() y no a mano. No es un
 * capricho de estilo: el controlador tenía salidas tempranas dentro de un
 * beginTransaction() sin cerrar, y una fecha mal escrita dejaba la fila
 * bloqueada hasta que la conexión moría por timeout. Con el cierre no hay
 * forma de salir sin cerrar.
 */
class ProgramacionContratoService
{
    /** Tipos de trabajo que son la misma cosa a efectos de duplicados. */
    private const RP_EQUIVALENTES = ['10444', '12161'];

    /** Horario que se asume al fijar una jornada. */
    private const HORA_INICIO = '06:59:00 a.m.';
    private const HORA_FINAL  = '04:59:00 p.m.';

    public function __construct(
        private ProgramacionService $ejecutados
    ) {}

    /**
     * Busca un contrato en la base de programación para precargar el formulario.
     *
     * @return array|null null cuando no hay nada que devolver —contrato no
     *                    numérico, vacío o inexistente—, que es como la vista
     *                    distingue "sin resultados".
     */
    public function buscarEnBase($contrato): ?array
    {
        $contrato = trim((string) $contrato);

        if ($contrato === '' || ! is_numeric($contrato)) {
            return null;
        }

        $datos = TblProgramacionBase::where('CONTRATO', $contrato)->first();

        if ($datos === null) {
            return null;
        }

        // 1 y 2 son recepciones ya ejecutadas: no se vuelve a programar.
        if (in_array((string) $datos->ESTADO_RECEPCION, ['1', '2'], true)) {
            return ['errors' => 'El contrato ya ha sido ejecutado'];
        }

        // El técnico se muestra como "id. apellidos nombres".
        $inspector = TblInspCali::where('id', $datos->ID_TECNICO)->first();

        $datos->ID_TECNICO = $inspector
            ? $datos->ID_TECNICO . '. ' . $inspector->apellidos . ' ' . $inspector->nombres
            : null;

        return $datos->toArray();
    }

    /**
     * Da de alta un contrato en una programación.
     *
     * Antes de guardar comprueba dos cosas: que el contrato no esté ya
     * ejecutado —según las bitácoras— y que no exista ya programado. Las dos
     * devuelven un aviso en vez de un error: la vista los enseña y deja al
     * usuario decidir.
     *
     * @param array $data Fila del formulario, indexada de 1 a 17.
     * @param mixed $tabla Id de la programación a la que pertenece.
     * @param bool  $quejaConfirmada El usuario ya vio el aviso de PQRS y quiso
     *                               programar igual, así que no se repregunta.
     */
    public function crear(array $data, $tabla, bool $quejaConfirmada = false): array
    {
        /* La queja va primero: es la que cambia lo que hay que hacer con el
           contrato, y conviene verla aunque después resulte que ya se ejecutó
           o que ya estaba programado. Los otros dos avisos son un no rotundo;
           este solo pregunta, así que confirmarlo no se salta lo que sigue. */
        if (! $quejaConfirmada) {
            if ($aviso = $this->avisoPorQuejaAbierta($data)) {
                return $aviso;
            }
        }

        if ($aviso = $this->avisoPorEjecutado($data)) {
            return $aviso;
        }

        if ($aviso = $this->avisoPorDuplicado($data)) {
            return $aviso;
        }

        $programacion = DB::transaction(function () use ($data, $tabla) {
            $programacion = new TblProgramacionContrato();

            $programacion->CONTRATO           = $data[1];
            $programacion->TIPO_TRABAJO       = $data[2];
            $programacion->FECHA              = $data[3];
            $programacion->CELULAR            = $data[4];
            $programacion->NOMBRE_USUARIO     = $data[5];
            $programacion->ORDEN_TRABAJO      = $data[6];
            $programacion->DIRECCION          = $data[7];
            $programacion->BARRIO             = $data[8];
            $programacion->CIUDAD             = $data[9];
            $programacion->ACTIVA             = $data[10];
            $programacion->SUSPENDIDO         = $data[11];
            $programacion->CATEGORIA          = $data[12];
            $programacion->FECHA_AGENDAMIENTO = $data[13];
            $programacion->OBSERVACIONES      = $data[14];
            $programacion->PORQUE_PROGRAMO    = $data[15];
            $programacion->TECNICO            = $data[16];
            $programacion->JORNADA            = $data[17];
            $programacion->id_programacion    = $tabla;

            $programacion->save();

            return $programacion;
        });

        return ['message' => 'Registro guardado correctamente', 'id' => $programacion->id];
    }

    /**
     * Alta manual desde la pantalla de creación.
     *
     * Va aparte de crear(): aquí los datos llegan con nombre de campo y no por
     * posición, no se comprueba duplicado ni ejecutado —quien la da de alta ya
     * sabe lo que hace— y la fila queda marcada como `plantilla`, que es lo que
     * la hace aparecer en el agendamiento aunque no cruce con la base.
     */
    public function crearDesdePlantilla(array $datos, $tabla): array
    {
        $programacion = DB::transaction(function () use ($datos, $tabla) {
            $c = new TblProgramacionContrato();

            foreach ([
                'CONTRATO', 'TIPO_TRABAJO', 'FECHA', 'CELULAR', 'NOMBRE_USUARIO',
                'ORDEN_TRABAJO', 'DIRECCION', 'BARRIO', 'CIUDAD', 'ACTIVA',
                'SUSPENDIDO', 'CATEGORIA', 'FECHA_AGENDAMIENTO', 'OBSERVACIONES',
                'PORQUE_PROGRAMO', 'TECNICO', 'JORNADA',
            ] as $campo) {
                $c->$campo = $datos[$campo] ?? null;
            }

            $c->HORA_INICIO = self::HORA_INICIO;
            $c->HORA_FINAL = self::HORA_FINAL;
            $c->id_programacion = $tabla;
            $c->plantilla = 1;
            $c->save();

            return $c;
        });

        return ['message' => 'Registro guardado correctamente', 'id' => $programacion->id];
    }

    /**
     * Cambia un solo campo de un contrato programado.
     *
     * @return array Con `error` y `estado` cuando no se pudo, si no el mensaje.
     */
    public function actualizarCampo($id, ?string $campo, $valor): array
    {
        if ($campo === 'FECHA_AGENDAMIENTO' && ! $this->fechaValida($valor)) {
            return [
                'error'  => 'La fecha debe tener el formato correcto (Y-m-d).',
                'estado' => 422,
            ];
        }

        DB::transaction(function () use ($id, $campo, $valor) {
            $programacion = TblProgramacionContrato::find($id);

            // Fijar la jornada arrastra el horario que se le supone.
            if ($campo === 'JORNADA') {
                $programacion->HORA_INICIO = self::HORA_INICIO;
                $programacion->HORA_FINAL  = self::HORA_FINAL;
            }

            $programacion->$campo = $valor;
            $programacion->save();
        });

        return ['message' => 'Registro actualizado correctamente'];
    }

    public function eliminar($id): array
    {
        DB::transaction(fn () => TblProgramacionContrato::find($id)?->delete());

        return ['message' => 'Registro eliminado correctamente'];
    }

    /**
     * Fecha exacta en formato Y-m-d.
     *
     * Carbon acepta '2024-1-9' y lo normaliza, así que no basta con que parsee:
     * se compara el resultado con lo que llegó.
     */
    private function fechaValida($valor): bool
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $valor)->format('Y-m-d') === $valor;
        } catch (\Exception $e) {
            return false;
        }
    }

    /** El contrato ya se ejecutó según las bitácoras. */
    private function avisoPorEjecutado(array $data): ?array
    {
        $ejecutado = $this->ejecutados->findExecuted($data[1], $data[2], $data[6]);

        /* La excepción del SA 12164 —cerrado con defecto se vuelve a
           inspeccionar— vive ahora en findExecuted, para que valga igual aquí
           que en las cargas masivas y en el call center. */
        if (! $ejecutado) {
            return null;
        }

        $inspector = TblInspCali::where('cedula', $ejecutado->CC_OPERARIO)->first();

        return [
            'movilidad'    => 'Contrato ya ejecutado',
            'usuario'      => $inspector->apellidos . ' ' . $inspector->nombres,
            'agendamiento' => explode(' ', $ejecutado->FECHA)[0],
        ];
    }

    /**
     * El contrato tiene una PQRS sin resolver.
     *
     * Abierta es lo que dice coordinación: sigue en estado 1 y todavía no tiene
     * fecha de legalización. Las dos condiciones van juntas porque la queja se
     * cierra en dos pasos y una sola no basta para darla por terminada.
     *
     * No impide programar —muchas veces la visita es justo la respuesta a la
     * queja—, solo devuelve con qué avisar para que quien programa decida.
     */
    private function avisoPorQuejaAbierta(array $data): ?array
    {
        $queja = AsignadasQuejas::where('CONTRATO', trim((string) $data[1]))
            ->where('estado', 1)
            ->where(function ($consulta) {
                $consulta->whereNull('FECHA_LEGALIZACION')
                    ->orWhere('FECHA_LEGALIZACION', '');
            })
            ->orderByDesc('id')
            ->first();

        if ($queja === null) {
            return null;
        }

        return ['queja' => [
            'motivo'      => $queja->MOTIVO_DE_PQR ?: 'sin motivo registrado',
            'solicitud'   => $queja->NUMERO_SOLICITUD,
            'responsable' => $queja->RESPONSABLE ?: 'sin responsable asignado',
            'limite'      => $queja->FECHA_LIMITE,
            // Negativo es queja vencida; la vista lo redacta.
            'dias'        => is_numeric($queja->DIAS_FALTANTES) ? (int) $queja->DIAS_FALTANTES : null,
        ]];
    }

    /** Ya hay una programación para ese contrato. */
    private function avisoPorDuplicado(array $data): ?array
    {
        $consulta = TblProgramacionContrato::where('CONTRATO', $data[1]);

        /* Para revisión periódica los dos códigos son el mismo trabajo, así que
           el duplicado se busca por cualquiera de ellos y sin mirar la orden.
           Para el resto, el par contrato + orden es lo que identifica. */
        if (in_array($data[2], self::RP_EQUIVALENTES, true)) {
            $consulta->whereIn('TIPO_TRABAJO', self::RP_EQUIVALENTES);
        } else {
            $consulta->where('ORDEN_TRABAJO', $data[6]);
        }

        $existente = $consulta->first();

        if (! $existente) {
            return null;
        }

        return [
            'exist'        => 'Ya existe una programación con estos datos',
            'id'           => $existente->id,
            'usuario'      => $existente->PORQUE_PROGRAMO,
            'agendamiento' => $existente->FECHA_AGENDAMIENTO,
        ];
    }
}
