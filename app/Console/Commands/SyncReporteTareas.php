<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movilidad;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncReporteTareas extends Command
{
    // El nombre del comando que ejecutaremos
    protected $signature = 'app:sync-tareas';

    // Descripción para la consola
    protected $description = 'Extrae Tasks priorizando efectividad y fecha (agrupando por Contrato, Tipo y Dirección)';

    public function handle()
    {
        $this->info('Iniciando sincronización de tareas...');

        // Calculamos las fechas dinámicamente
        $inicio = Carbon::now()->subDay()->startOfDay();
        $fin = Carbon::now()->endOfDay();

        $this->info('Consultando y guardando por bloques...');

        $estadosCierre = [
            '.CERTIFICADA',
            'CERTIFICADA CON NOVEDADES',
            '.INSPECCIONADA CON DEFECTO CRITICO VALLE',
            '.INSPECCIONADA CON DEFECTO NO CRITICO VALLE'
        ];

        // Usamos chunk(500) en lugar de get()
        Movilidad::select([
            'id', 'NroSitio', 'IdTarea', 'Direccion', 'Depto', 'Localidad',
            'FechaRealFin', 'NroOperario', 'NombreOperario',
            'NombreSitio', 'TipoTarea', 'Prioridad',
            'Cierre1', 'Cierre2', 'Cierre3', 'AttrCategoria'
        ])
            ->where('Grupo', 'INSP-VALLE')
            ->where('Cierre3', '<>', 'CIERRE ADMINISTRATIVO')
            ->whereBetween('FechaRealFin', [$inicio, $fin])
            ->chunk(500, function ($tareas) use ($estadosCierre) {

                // =========================================================================
                // 1. FILTRAR DUPLICADOS DENTRO DEL BLOQUE (NroSitio + TipoTarea + Direccion)
                // =========================================================================
                $tareasFiltradas = $tareas->groupBy(function ($t) {
                    // Agregamos la dirección a la llave compuesta
                    return $t->NroSitio . '_' . $t->TipoTarea . '_' . trim($t->Direccion);
                })->map(function ($grupo) use ($estadosCierre) {
                    // Ordenamos todo el grupo por fecha (el más reciente primero)
                    $grupoOrdenado = $grupo->sortByDesc('FechaRealFin');

                    // Buscamos la efectiva más reciente
                    $efectiva = $grupoOrdenado->first(function ($t) use ($estadosCierre) {
                        return in_array(strtoupper(trim($t->Cierre3)), $estadosCierre);
                    });

                    // Retornamos la efectiva más nueva. Si no hay efectivas, la fallida más nueva.
                    return $efectiva ? $efectiva : $grupoOrdenado->first();
                })->values();

                // Extraemos los contratos de este bloque para verificar la BD local
                $nroSitiosChunk = $tareasFiltradas->pluck('NroSitio')->unique()->toArray();

                // Buscamos tareas existentes con esos Contratos
                $tareasExistentes = DB::table('reportes_diarios')
                    ->whereIn('NroSitio', $nroSitiosChunk)
                    ->get(['id', 'NroSitio', 'TipoTarea', 'Direccion', 'Cierre3', 'FechaRealFin']) // <-- Incluimos Direccion
                    ->groupBy(function ($item) {
                        return $item->NroSitio . '_' . $item->TipoTarea . '_' . trim($item->Direccion); // <-- Incluimos Direccion
                    });

                // =========================================================================
                // 2. EXTRACCIÓN DE DATOS PARA CÁLCULO DE MESES
                // =========================================================================
                $contratosLimpios = $tareasFiltradas->pluck('NroSitio')->map(function($sitio) {
                    return ltrim($sitio, ':');
                })->filter()->unique()->toArray();

                $mesesPorContrato = DB::table('tbl_programacion_base')
                    ->whereIn('CONTRATO', $contratosLimpios)
                    ->pluck('MESES', 'CONTRATO');

                $asignacionesRespaldo = DB::table('tbl_asignaciones')
                    ->whereIn('CONTRATO', $contratosLimpios)
                    ->get(['CONTRATO', 'ID_TIPO_TRABAJO', 'FECHA_ULTCERTI'])
                    ->groupBy('CONTRATO');

                $fechaCalculo = Carbon::now();

                $datosInsertar = [];
                $idTareasParaBorrar = []; // Almacenaremos el 'id' principal de la tabla para borrar

                // =========================================================================
                // 3. PROCESAMIENTO Y CRUCE (Lógica de reemplazo)
                // =========================================================================
                foreach ($tareasFiltradas as $tarea) {
                    $array = $tarea->toArray();

                    // Agregamos la dirección a la llave para verificar si ya existe en la BD
                    $llave = $array['NroSitio'] . '_' . $array['TipoTarea'] . '_' . trim($array['Direccion']);
                    $procesar = true;

                    $cierreNuevo = strtoupper(trim($array['Cierre3']));
                    $esEfectivoNuevo = in_array($cierreNuevo, $estadosCierre);
                    $fechaNueva = Carbon::parse($array['FechaRealFin'] ?? '1900-01-01');

                    // Verificamos si esa combinación ya existe en la BD local
                    if ($tareasExistentes->has($llave)) {
                        $registrosViejos = $tareasExistentes->get($llave)->sortByDesc('FechaRealFin');

                        $viejoEfectivo = $registrosViejos->first(function ($r) use ($estadosCierre) {
                            return in_array(strtoupper(trim($r->Cierre3)), $estadosCierre);
                        });

                        $mejorViejo = $viejoEfectivo ? $viejoEfectivo : $registrosViejos->first();
                        $esEfectivoViejo = $viejoEfectivo !== null;
                        $fechaVieja = Carbon::parse($mejorViejo->FechaRealFin ?? '1900-01-01');

                        if ($esEfectivoViejo && !$esEfectivoNuevo) {
                            // La vieja es efectiva y la nueva no -> Ignoramos la nueva
                            $procesar = false;
                        } elseif (!$esEfectivoViejo && $esEfectivoNuevo) {
                            // La nueva es efectiva y la vieja no -> Borramos todas las viejas
                            $idsToDrop = $registrosViejos->pluck('id')->reject(function($viejoId) use ($array) {
                                return $viejoId == $array['id']; // Excluimos el id actual por si es el mismo
                            })->toArray();
                            $idTareasParaBorrar = array_merge($idTareasParaBorrar, $idsToDrop);
                        } else {
                            // Empate -> Gana la fecha más reciente (Usando gte() para compatibilidad)
                            if ($fechaNueva->gte($fechaVieja)) {
                                $idsToDrop = $registrosViejos->pluck('id')->reject(function($viejoId) use ($array) {
                                    return $viejoId == $array['id'];
                                })->toArray();
                                $idTareasParaBorrar = array_merge($idTareasParaBorrar, $idsToDrop);
                            } else {
                                $procesar = false;
                            }
                        }
                    }

                    // Si la nueva tarea superó los filtros, calculamos meses y guardamos
                    if ($procesar) {
                        $contratoLimpio = ltrim($array['NroSitio'], ':');
                        $mesesCalculados = $mesesPorContrato[$contratoLimpio] ?? null;

                        if ($mesesCalculados === null && isset($asignacionesRespaldo[$contratoLimpio])) {
                            $tareaLimpia = trim(substr($array['TipoTarea'], 2));
                            $match = $asignacionesRespaldo[$contratoLimpio]->first(function ($item) use ($tareaLimpia) {
                                return $item->ID_TIPO_TRABAJO == $tareaLimpia ||
                                    ($tareaLimpia == '10444' && $item->ID_TIPO_TRABAJO == '12161');
                            });

                            if ($match && !empty($match->FECHA_ULTCERTI)) {
                                try {
                                    $strFecha = str_replace('/', '-', trim($match->FECHA_ULTCERTI));
                                    $fechaUlt = Carbon::parse($strFecha);
                                    $mesesCalculados = (int) floor($fechaUlt->diffInMonths($fechaCalculo));
                                } catch (\Exception $e) { }
                            }
                        }

                        $array['Meses'] = $mesesCalculados;
                        $array['created_at'] = now();
                        $array['updated_at'] = now();
                        $datosInsertar[] = $array;
                    }
                }

                // =========================================================================
                // 4. EJECUCIÓN EN BASE DE DATOS LOCAL
                // =========================================================================

                // Borramos usando el campo "id" primario de la base de datos
                if (!empty($idTareasParaBorrar)) {
                    DB::table('reportes_diarios')
                        ->whereIn('id', array_unique($idTareasParaBorrar))
                        ->delete();
                }

                if (!empty($datosInsertar)) {
                    DB::table('reportes_diarios')->upsert(
                        $datosInsertar,
                        ['id'],
                        [
                            // ¡Aquí quitamos 'Meses'! Se insertará si es nuevo, pero no se actualizará si ya existe.
                            'NroSitio', 'IdTarea', 'Direccion', 'Depto', 'Localidad', 'FechaRealFin',
                            'NroOperario', 'NombreOperario', 'NombreSitio', 'TipoTarea',
                            'Prioridad', 'Cierre1', 'Cierre2', 'Cierre3', 'AttrCategoria',
                            'updated_at'
                        ]
                    );
                }
            });

        $this->info('¡Sincronización completada con éxito!');
        return Command::SUCCESS;
    }
}
