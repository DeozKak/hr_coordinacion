<?php

namespace App\Services\Produccion;

use App\Jobs\CorreoProduccion;
use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Produccion\TblProduccionHistorico;
use App\Models\TblInspCali;
use Illuminate\Support\Carbon;

/**
 * El detalle de un día: los contratos que un inspector cerró en una fecha.
 */
class ContratoDiarioService
{
    /**
     * Nombre de archivo de bitácora: "Bitacora Valle_dd-mm-aaaa____dd-mm-aaaa Supervisor".
     *
     * La bitácora a la que pertenece un contrato no se guarda en ninguna
     * columna: hay que deducirla del nombre del archivo, cruzando el rango de
     * fechas con el supervisor del inspector.
     */
    private const PATRON_ARCHIVO = '/Bitacora Valle_(\d{2}-\d{2}-\d{4})____(\d{2}-\d{2}-\d{4}) (.+)$/';

    /** Contratos del inspector en esa fecha, con el nombre ya compuesto. */
    public function contratosDelDia(string $fecha, $cedula)
    {
        return TblBitacoraContrato::selectRaw(
            "tbl_bitacora_contratos.id,
             CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS nombre_completo,
             tbl_bitacora_contratos.CC_OPERARIO, tbl_bitacora_contratos.MUNICIPIO,
             tbl_bitacora_contratos.FECHA, tbl_bitacora_contratos.No_ACTA,
             tbl_bitacora_contratos.TIPO_TRABAJO, tbl_bitacora_contratos.CONTRATO,
             tbl_bitacora_contratos.ORDEN_TRABAJO, tbl_bitacora_contratos.ORDEN_EXT,
             tbl_bitacora_contratos.CATEGORIA, tbl_bitacora_contratos.RESULTADO_CIERRE,
             tbl_bitacora_contratos.HORA_INICIO, tbl_bitacora_contratos.HORA_FINAL,
             tbl_bitacora_contratos.DURACION_INSP, tbl_bitacora_contratos.`4_RECINTOS`,
             tbl_bitacora_contratos.state, tbl_bitacora_contratos.diseno_especial,
             tbl_bitacora_contratos.vence"
        )
            ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_contratos.CC_OPERARIO')
            ->where('tbl_bitacora_contratos.CC_OPERARIO', $cedula)
            ->where('tbl_bitacora_contratos.FECHA', $fecha)
            ->get();
    }

    /** Cuántos contratos hay de cada prioridad ese día. */
    public function contadoresPorPrioridad(string $fecha, $cedula): array
    {
        return TblBitacoraContrato::selectRaw('PRIORIDAD, COUNT(*) AS total')
            ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_contratos.CC_OPERARIO')
            ->where('tbl_bitacora_contratos.CC_OPERARIO', $cedula)
            ->where('tbl_bitacora_contratos.FECHA', $fecha)
            ->groupBy('tbl_bitacora_contratos.PRIORIDAD')
            ->get()
            ->toArray();
    }

    /**
     * Cómo quedaron los dobles de ese día para ese inspector.
     *
     * @return array{festivoExcluido: bool, sabadosManuales: array, totalSabado: int}
     */
    public function estadoDeDobles(?TblProduccionHistorico $historico, string $fecha, $cedula): array
    {
        $festivoExcluido = $this->tieneFecha(
            json_decode($historico?->no_dobles_festivos ?? '', true), $cedula, $fecha
        );

        $sabadosManuales = [];
        $manuales = json_decode($historico?->dobles_sabados ?? '', true);

        if ($manuales !== null) {
            foreach ($manuales as $grupo) {
                foreach ($grupo as $registro) {
                    if ($registro['datos']['cc_inspector'] != $cedula) {
                        continue;
                    }

                    foreach ($registro['datos']['totalInspecciones'] as $inspeccion) {
                        if ($inspeccion['fecha'] == $fecha) {
                            $sabadosManuales[] = [$inspeccion['total_contratos'], true];
                        }
                    }
                }
            }
        }

        return [
            'festivoExcluido' => $festivoExcluido,
            'sabadosManuales' => $sabadosManuales,
            'totalSabado'     => $this->totalSabadoCalculado($historico, $fecha, $cedula),
        ];
    }

    /** Lo que el último cálculo dejó anotado como doble para ese sábado. */
    private function totalSabadoCalculado(?TblProduccionHistorico $historico, string $fecha, $cedula): int
    {
        $data = json_decode($historico?->data ?? '', true);

        foreach ($data['sabadodobles'] ?? [] as $entrada) {
            foreach ($entrada['datos'] as $dato) {
                if ($dato['fecha'] == $fecha && $dato['cc_inspector'] == $cedula) {
                    return (int) ($dato['totalContratosSabado'] ?? 0);
                }
            }
        }

        return 0;
    }

    /** ¿Aparece esa fecha en una de las listas de inspecciones del inspector? */
    private function tieneFecha(?array $lista, $cedula, string $fecha): bool
    {
        if ($lista === null) {
            return false;
        }

        foreach ($lista as $grupo) {
            foreach ($grupo as $registro) {
                if ($registro['datos']['cc_inspector'] != $cedula) {
                    continue;
                }

                foreach ($registro['datos']['totalInspecciones'] as $inspeccion) {
                    if ($inspeccion['fecha'] == $fecha) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /** Cambia una columna de un contrato desde la rejilla. */
    public function actualizarCampo($id, ?string $campo, $valor): bool
    {
        if ($campo === null || $valor === null) {
            return false;
        }

        $contrato = TblBitacoraContrato::findOrFail($id);
        $contrato->{$campo} = $valor;
        $contrato->save();

        return true;
    }

    /** Saca o devuelve el contrato a la producción, sin borrarlo. */
    public function alternarEstado($id): void
    {
        $contrato = TblBitacoraContrato::findOrFail($id);
        $contrato->state = $contrato->state === 1 ? 0 : 1;
        $contrato->save();
    }

    /** Marca o desmarca el contrato como diseño especial. */
    public function alternarDisenoEspecial($id): ?bool
    {
        $contrato = TblBitacoraContrato::find($id);

        if ($contrato === null) {
            return null;
        }

        $contrato->diseno_especial = ! $contrato->diseno_especial;
        $contrato->save();

        return (bool) $contrato->diseno_especial;
    }

    /**
     * Añade a mano un contrato que no vino en la bitácora.
     *
     * @return string|null el mensaje de error, o null si entró bien.
     */
    public function insertar(array $datos, string $usuario): ?string
    {
        $cedula = $datos[2];
        $fecha = Carbon::createFromFormat('d-m-y', $datos[4]);

        $bitacora = $this->bitacoraDe($fecha->format('d-m-Y'), $cedula);

        if (! $bitacora instanceof TblBitacoraArchivo) {
            return 'No se encontró bitácora para asociar.';
        }

        $inspector = TblInspCali::select('apellidos', 'nombres')->where('cedula', $cedula)->first();

        $contrato = new TblBitacoraContrato();
        $contrato->CC_OPERARIO      = $cedula;
        $contrato->MUNICIPIO        = $datos[3];
        $contrato->FECHA            = $fecha->format('Y-m-d');
        $contrato->No_ACTA          = $datos[5];
        $contrato->TIPO_TRABAJO     = $datos[6];
        $contrato->CONTRATO         = $datos[7];
        $contrato->ORDEN_TRABAJO    = $datos[8];
        $contrato->ORDEN_EXT        = $datos[9];
        $contrato->CATEGORIA        = $datos[10];
        $contrato->RESULTADO_CIERRE = $datos[11];
        $contrato->HORA_INICIO      = $datos[12];
        $contrato->HORA_FINAL       = $datos[13];
        $contrato->DURACION_INSP    = $datos[14];
        $contrato->{'4_RECINTOS'}   = $datos[15];
        $contrato->id_bitacora      = $bitacora->id;
        $contrato->state            = 1;
        $contrato->save();

        // Coordinación se entera por correo de todo lo que se añade a mano.
        CorreoProduccion::dispatch($datos[7], $usuario, $fecha->format('Y-m-d'), $inspector);

        return null;
    }

    /**
     * La bitácora a la que pertenece un contrato de esa fecha e inspector.
     *
     * @return TblBitacoraArchivo|array el archivo, o un array con el error.
     */
    public function bitacoraDe(string $fecha, $cedula)
    {
        $inspector = TblInspCali::select('users.name AS supervisor')
            ->join('users', 'users.id', '=', 'tbl_insp_cali.SUPERVISOR')
            ->where('tbl_insp_cali.cedula', $cedula)
            ->first();

        if (! $inspector) {
            return ['error' => 'Supervisor no encontrado.'];
        }

        $supervisor = str_replace(' ', '', $inspector->supervisor);
        $dia = Carbon::createFromFormat('d-m-Y', $fecha);

        foreach (TblBitacoraArchivo::select('id', 'nombre_archivo')->get() as $archivo) {
            if (! preg_match(self::PATRON_ARCHIVO, $archivo->nombre_archivo, $partes)) {
                continue;
            }

            $desde = Carbon::createFromFormat('d-m-Y', $partes[1]);
            $hasta = Carbon::createFromFormat('d-m-Y', $partes[2]);

            if ($dia->between($desde, $hasta) && str_replace(' ', '', $partes[3]) === $supervisor) {
                return $archivo;
            }
        }

        return ['error' => 'Bitácora no encontrada.'];
    }
}
