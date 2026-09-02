<?php

namespace App\Http\Controllers\Coordinacion;

use App\Http\Controllers\Controller;
use App\Models\Bitacoras\tbl_bitacora_contrato;
use App\Models\Bitacoras\tbl_dv_insp;
use App\Models\Coordinacion\asignadas;
use App\Models\Coordinacion\TblCausasCierre;
use App\Models\Coordinacion\TblRecepcion;
use App\Models\Coordinacion\TblRecepcionVneDetalle;
use App\Models\Programacion\tbl_programacion_contrato;
use App\Models\tbl_insp_cali;
use App\Models\Zonificacion\tbl_localidades_municipio;
use App\Models\Zonificacion\tbl_localidades_sede;
use App\Models\Zonificacion\TblBarrios;
use App\Models\Zonificacion\TblGrupo;
use App\Models\Zonificacion\TblGruposDetalle;
use App\Models\Zonificacion\TblSubgrupo;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use ZipArchive;

class CoordinacionController extends Controller
{
    public function coordinacion()
    {
        set_time_limit(400);

        $inspectors = tbl_insp_cali::all();
        $groups = TblGrupo::all();
        $subgroups = TblSubgrupo::all();

        $query = asignadas::select("*")
            ->whereIn('asignadas.tipo_trabajo', [10444, 12161])
            ->where('asignadas.status', 1);
        $completeData = $query->get();

        $fechaActual = new DateTime();

       /* foreach ($completeData as $item) {

            if ($item->tipo_trabajo == "12161") {
                $tipoOrden = "Ext." . $item->tipo_trabajo;
            } else {
                if ($item->orden_solicitud_externa != null) {
                    $tipoOrden = "Ext." . $item->tipo_solicitud_externa;
                } else {
                    $tipoOrden = "Masiva";
                }
            }

            if ($item->fecha_ult_cert != '1970-01-01') {
                // CALCULO DE LOS MESES
                $fechaCertificacion = new DateTime($item->fecha_ult_cert); // Fecha de certificación
                // Obtener la diferencia entre las dos fechas
                $diferencia = $fechaCertificacion->diff($fechaActual);
                // Calcular los meses transcurridos
                $mesesTranscurridos = ($diferencia->y * 12) + $diferencia->m;
                // Verificar si los días adicionales suman un mes más
                if ($diferencia->d > 0) {
                    $mesesTranscurridos++;
                }
                // revisar cada dia que pasa esta funcionalidad
                if ($mesesTranscurridos >= 60) {
                    if ($tipoOrden == "Masiva") {
                        $fechaLimite = (clone $fechaCertificacion)->modify('+59 months');
                        $diferenciaDias = "-" . $fechaLimite->diff($fechaActual)->days;
                        // tomamos la fecha de creacion del registro
                        $fechaCreacion = explode(" ", $item->created_at)[0];
                        $diferenciaDias = intval($diferenciaDias);
                        $diferenciaDias = $this->calcularDiasRestantesMenor60meses($fechaActual->format('Y-m-d'), $fechaLimite->format('Y-m-d'));
                    } else {
                        if ($item->orden_solicitud_externa != null) {
                            $fechaCreacion = $item->fecha_reasignacion_externa;
                        } else {
                            $fechaCreacion = explode(" ", $item->created_at)[0];
                        }

                        if ($item->estado_producto == "Activo") {
                            $diferenciaDias = 3;
                        } else if ($item->estado_producto == "Suspendido") {
                            $diferenciaDias = 2;
                        }
                        $diferenciaDias = $this->calcularDiasRestantes($fechaCreacion, $diferenciaDias);
                    }
                } else {
                    if ($tipoOrden == "Masiva") {
                        $fechaLimite = (clone $fechaCertificacion)->modify('+60 months');
                        $diferenciaDias = $this->calcularDiasRestantesMenor60meses($fechaActual->format('Y-m-d'), $fechaLimite->format('Y-m-d'));
                    } else if ($item->estado_producto == "Activo") {
                        if ($item->orden_solicitud_externa != null) {
                            $fechaCreacion = $item->fecha_reasignacion_externa;
                        } else {
                            $fechaCreacion = explode(" ", $item->created_at)[0];
                        }
                        $diferenciaDias = 6;

                        $diferenciaDias = $this->calcularDiasRestantes($fechaCreacion, $diferenciaDias);
                    }
                }
            } else {
                $fechaVence = new DateTime($item->vence);
                $fechaLimite = (clone $fechaVence)->modify('-1 months');

                $diferenciaDias = $this->calcularDiasRestantesMenor60meses($fechaActual->format('Y-m-d'), $fechaLimite->format('Y-m-d'));

                $diferenciaDias = $diferenciaDias - 1;
            }
            if ($item->dias_ejecutar != $diferenciaDias) {
                // actualizamos los dias para ejecutar
                asignadas::where('orden', $item->orden)
                            ->where('status', 1)
                            ->update(['dias_ejecutar' => $diferenciaDias]);
            }
        }*/

        // traemos las sedes para crear el selector en el modal de impresion masiva
        $sedes = tbl_localidades_sede::all();

        return view('gestion.coordinacion', compact('inspectors', 'sedes'));
    }

    public function getdataCoordinacionRP(Request $request)
    {
        $porPagina = 100; // Cantidad de registros por página
        $pagina = $request->input('pagina', 1); // Obtener el número de página de la solicitud
        $offset = ($pagina - 1) * $porPagina;

        // Obtener los datos necesarios
        $query = asignadas::select("*")
            ->whereIn('asignadas.tipo_trabajo', [10444, 12161])
            ->where('status', 1);

        $totalResults = $query->count();

        $arrayEstPro = [
            'Aplaza visita',
            'Llamar de nuevo',
            'No autoriza',
            'No contesta',
            'Programada',
        ];

        $causasCierre = TblCausasCierre::all();

        // CONSULTAMOS LOS INSPECTORES
        $inspectores = tbl_insp_cali::select('id', 'nombres', 'apellidos')
            ->where('state', 1)
            ->get();

        $query->skip($offset);
        $query->take($porPagina);
        $datos = $query->get();

        // Crear un array para almacenar los datos con índice
        $datosConIndice = $datos->map(function ($item, $index) use ($offset) {

            // consultamos la tabla de recepcion con el id de la orden para traer ele estado de recepcion y la fecha de recepcion
            $queryRecepcion = TblRecepcion::where('ordenTrabajo', $item->orden)->first();

            if ($queryRecepcion != null) {

                $estadoRecepcion = $queryRecepcion->estadoRecepcion;
                $fechaRecepcion = explode(" ", $queryRecepcion->created_at)[0];

                // consultamos la tabla detalle para traer el ultimo registro de cada orden y el total
                $queryDetalleVne = TblRecepcionVneDetalle::where('ordenTrabajo', $queryRecepcion->ordenTrabajo);
                $queryDetalleVne->orderBy('id', 'desc')->limit(1);
                $detalleRecepcion = $queryDetalleVne->get();

                if (isset($detalleRecepcion[0])) {
                    $totalVneOrden = $queryDetalleVne->count();

                    $fecha = explode(" ", $detalleRecepcion[0]->created_at)[0];
                    $timestamp = strtotime($fecha);
                    $fechaLegible = gmdate('d-M-y', $timestamp);
                    $fechaUltimaVne = strtolower($fechaLegible);
                    $ultimaVne = $detalleRecepcion[0]->idVne;

                    // consultamos los inspectores para sacar el id del ultimo inspector
                    $queryInspectores = tbl_insp_cali::where('cedula', $detalleRecepcion[0]->ccOperario)->first();

                    $inspectorUltimaVne = $queryInspectores->id;

                    $compiladoObservacion = $detalleRecepcion[0]->comObservacion;
                }
            } else {
                $totalVneOrden = "";
                $fechaUltimaVne = "";
                $ultimaVne = "";
                $inspectorUltimaVne = "";
                $compiladoObservacion = "";
                $fechaRecepcion = "";
                $estadoRecepcion = "";
            }

            if ($estadoRecepcion != "") {

                if ($item->tipo_trabajo == "12161") {
                    $columnaConsultar = "ORDEN_EXT";
                } else {
                    $columnaConsultar = "ORDEN_TRABAJO";
                }

                $contratoBuscar = ":" . $item->contrato;

                // consultamos la tabla de bitacoras
                $queryBitacora = tbl_bitacora_contrato::where($columnaConsultar, $item->orden)
                    ->where('CONTRATO', $contratoBuscar)
                    ->first();

                // consultamos en la tabla de devoluciones
                $queryDev = tbl_dv_insp::with('Supervisor')
                    ->where($columnaConsultar, $item->orden)
                    ->where('CONTRATO', $contratoBuscar)
                    ->first();

                if (($queryBitacora != null && $queryDev != null) || $queryBitacora != null) {
                    $numActa = $queryBitacora->No_ACTA;
                    $validacionFormato = "Aprobado";
                    $observacionRechazo = "";
                } else if ($queryDev != null) {
                    $numActa = $queryDev->No_ACTA;
                    $validacionFormato = "Rechazado";
                    $observacionRechazo = $queryDev->Supervisor->name . " " . $queryDev->FECHA_DV;
                }
            } else {
                $numActa = "";
                $validacionFormato = "";
                $observacionRechazo = "";
            }

            // consultamos la tabla de contratos con la orden
            $queryProCont = tbl_programacion_contrato::where('CONTRATO', $item->contrato)->first();
            if ($queryProCont != null) {
                $jornada = explode(" ", $queryProCont->HORA_INICIO);

                $jornada = $jornada[1];
                $celular = $queryProCont->CELULAR;
                $observaciones = $queryProCont->OBSERVACIONES;
                $fechaAgendamiento = $queryProCont->FECHA_AGENDAMIENTO;
            } else {
                $jornada = "";
                $celular = "";
                $observaciones = "";
                $fechaAgendamiento = "";
            }

            if ($item->tipo_trabajo == "12161") {
                $diaIngreso = explode(" ", $item->created_at)[0];
                $tipoOrden = "Ext." . $item->tipo_trabajo;
            } else {
                if ($item->orden_solicitud_externa != null) {
                    $diaIngreso = $item->fecha_reasignacion_externa;
                    $tipoOrden = "Ext." . $item->tipo_solicitud_externa;
                } else {
                    $diaIngreso = explode(" ", $item->created_at)[0];
                    $tipoOrden = "Masiva";
                }
            }

            try{
            $queryMunicipio = tbl_localidades_municipio::where(DB::raw('trim(nombre)'), trim($item->localidad))->first();
            // Obtenemos la sede directamente desde la consulta anterior
            $querySede = tbl_localidades_sede::where('id', $queryMunicipio->id_sede)->first();
            }catch (\Exception $e){
                log::error($e->getMessage());
            }
            // Consultamos primero el municipio
            $queryLugar = $queryMunicipio;
            $columnaLugar = 'id_mun';

            // Si no se encuentra el detalle por municipio, buscamos por barrio
            $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();

            if ($queryDetalle == null) {
                // Ahora buscamos por barrio si no encontramos el detalle por municipio
                $queryLugar = TblBarrios::where('barrio', $item->sector_operativo)->first();
                $columnaLugar = 'id_barrio';

                if ($queryLugar != null) {
                    $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();
                }
            }

            try{
            // Consultamos la tabla de grupos
            $queryGrupos = TblGrupo::where('id', $queryDetalle->id_grupo)->first();
            // Consultamos la tabla de subgrupos
            $querySubGrupos = TblSubgrupo::where('id', $queryDetalle->id_subGrupo)->first();
            }catch(\Exception $e){
              //  dd($queryMunicipio->nombre);
                log::error($e->getMessage());
            }

            // CALCULO DE LOS MESES
            if ($item->fecha_ult_cert != '1970-01-01') {
                $fechaCertificacion = new DateTime($item->fecha_ult_cert); // Fecha de certificación
                $fechaActual = new DateTime(); // Fecha actual
                // Obtener la diferencia entre las dos fechas
                $diferencia = $fechaCertificacion->diff($fechaActual);
                // Calcular los meses transcurridos
                $mesesTranscurridos = ($diferencia->y * 12) + $diferencia->m;
                // Verificar si los días adicionales suman un mes más
                if ($diferencia->d > 0) {
                    $mesesTranscurridos++;
                }
            } else {
                $mesesTranscurridos = 60;
            }

            if ($item->dias_ejecutar < 0) {
                $cumplePoliticas = "NO";
            } else {
                $cumplePoliticas = "SI";
            }

            if (strpos($item->estado_corte, "CONEXION")) {
                $substr = 14;
            } else if (strpos($item->estado_corte, "ORDEN")) {
                $substr = 31;
            } else if (strpos($item->estado_corte, "ORDEN DE CONEXION")) {
                $substr = 21;
            } else {
                $substr = 22;
            }

            $cartera = substr($item->estado_corte, 0, $substr);

            // FECHA VENCE CERTIFICADO
            $fechaVence = strtotime($item->vence);
            $fechaVence = gmdate('d-M-y', $fechaVence);
            $fechaVence = strtolower($fechaVence);

            $fechaUltCert = strtotime($item->fecha_ult_cert);
            $fechaUltCert = gmdate('d-M-y', $fechaUltCert);
            $fechaUltCert = strtolower($fechaUltCert);

            // Estados gestion
            // En campo
            if ($item->codigo_tecnico != null) {
                $fechaActual = new DateTime();
                //consultamos la tabla programacion contratos con el contrato
                $queryProgramacionContrato = tbl_programacion_contrato::where('CONTRATO', $item->contrato)->first();
                // consultamos la tabla de recepcion vne detalle para saber si la orden tiene volantes
                $queryVneDetalle = TblRecepcionVneDetalle::where('ordenTrabajo', $item->orden)->first();

                if ($queryProgramacionContrato == null && $queryVneDetalle == null) {
                    $estadoGestion = "En campo";
                }

                // En campo con volantes previos
                if ($queryVneDetalle != null) {
                    $estadoGestion = "En campo con volantes previos";
                }

                // En campo con programacion incumplida
                if ($queryProgramacionContrato != null && $queryVneDetalle == null && $item->estado_programacion == null) {
                    $estadoGestion = "En campo con programacion incumplida";
                }

                //En campo con programacion a futuro
                if ($queryProgramacionContrato != null && $queryProgramacionContrato->FECHA_AGENDAMIENTO > $fechaActual->format('Y-m-d')) {
                    $estadoGestion = "En campo con programacion a futuro";
                }
            } else {
                // validamos si es masiva o externa
                if ($item->orden_solicitud_externa != null || $item->tipo_trabajo == "12161") {
                    $estadoGestion = "Externa sin asignar";
                } else {
                    $estadoGestion = "Masiva sin asignar";
                }
            }
            //--------------

            // Ejecutada efectiva por legalizar
            if ($item->estado_programacion != null && $item->status == 1) {
                $estadoGestion = "Ejecutada efectiva por legalizar";
            }
            //--------------------------------

            //Pendiente anulacion
            if ($item->causa_cierre != null) {
                $estadoGestion = "Pendiente anulacion";
            }

            //Validar suspendido por cartera
            if (strpos($item->estado_corte, "3") !== false || strpos($item->estado_corte, "5") !== false) {
                $estadoGestion = "Validar suspendido por cartera";
            }

            if(!isset($queryGrupos)){
                $queryGrupos = "-";
                $estadoGestion = "Zona sin Asignar";
            }

            if(!isset($querySubGrupos)){
                $querySubGrupos = "-";
                $estadoGestion = "Zona sin Asignar";
            }

            return [
                'indice' => $index + 1 + $offset,
                'orden' => $item->orden,
                'contrato' => $item->contrato,
                'producto' => $item->producto,
                // 1. ASIGNACION BASE OSF
                'numero_solicitud' => $item->numero_solicitud,
                'tipo_solicitud' => $item->tipo_solicitud,
                'NIT_CC' => $item->NIT_CC,
                'nombre_lugar' => $item->nombre_lugar,
                'departamento' => $item->departamento,
                'localidad' => $item->localidad,
                'sector_operativo' => $item->sector_operativo,
                'direccion' => $item->direccion,
                'consecutivo_ruta' => $item->consecutivo_ruta,
                'telefono' => $item->telefono,
                'medidor' => $item->medidor,
                'categoria' => $item->categoria,
                'unidad_operativa' => $item->unidad_operativa,
                'tipo_trabajo' => $item->tipo_trabajo,
                'fecha_asignacion' => $item->fecha_asignacion,
                'observacion_solicitud' => $item->observacion_solicitud,
                // 2. INFORMACIÓN COMPLEMENTARIA 12161
                'orden_solicitud_externa' => $item->orden_solicitud_externa,
                'tipo_solicitud_externa' => $item->tipo_solicitud_externa,
                'fecha_solicitud_externa' => $item->fecha_solicitud_externa,
                'observacion_externa' => $item->observacion_externa,
                'fecha_reasignacion_externa' => $item->fecha_reasignacion_externa,
                // 3. PROGRAMACIÓN DE ORDENES
                'FECHA_AGENDAMIENTO' => $fechaAgendamiento,
                'jornada' => $jornada,
                'CELULAR' => $celular,
                'OBSERVACIONES' => $observaciones,
                'estado_programacion' => $item->estado_programacion,
                // 4. ASIGNACIÓN INSPECTOR
                'codigo_tecnico' => $item->codigo_tecnico,
                'fecha_asignacion_inspector' => $item->fecha_asignacion_inspector,
                // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
                'estado_recepcion' => $estadoRecepcion,
                'fecha_recepcion' => $fechaRecepcion,
                'cantidad_vne' => $totalVneOrden,
                'ultima_vne' => $ultimaVne,
                'fecha_ultima_vne' => $fechaUltimaVne,
                'inspector_ultima_vne' => $inspectorUltimaVne,
                'compilado_observacion' => $compiladoObservacion,
                'causa_cierre' => $item->causa_cierre,
                'fecha_solicitud_cierre' => $item->fecha_solicitud_cierre,
                // 6.GESTIÓN REALIZADA OFICINA
                'num_acta' => $numActa,
                'validacion_formato' => $validacionFormato,
                'observacion_rechazo' => $observacionRechazo,
                // 7. FORMULACIÓN Y CALCULO
                'dia_ingreso' => $diaIngreso,
                'tipo_orden' => $tipoOrden,
                'sede' => $querySede->nombre,
                'grupo' => (isset($queryGrupos) && is_object($queryGrupos)) ? $queryGrupos->grupo : $queryGrupos,
                'subgrupo' => (isset($querySubGrupos) && is_object($querySubGrupos)) ? $querySubGrupos->subgrupo : $querySubGrupos,

                'meses' => $mesesTranscurridos,
                'fecha_vence_certificado' => $fechaVence,
                'dias_ejecutar' => $item->dias_ejecutar,
                'cumplimiento_politicas' => $cumplePoliticas,
                'cartera' => $cartera,
                'consumo' => $item->estado_producto,
                'fecha_ult_cert' => $fechaUltCert,
                'estado_gestion' => $estadoGestion,
                'ult_comentario' => $item->ult_comentario,
                'nom_inspector' => $item->nom_inspector,
                'marca' => $item->marca
            ];
        });

        // retornamos los tecnicos y el datosConIndice
        return response()->json(
            [
                'estadoProgramacion' => $arrayEstPro,
                'inspectores' => $inspectores,
                'causasCierre' => $causasCierre,
                'data' => $datosConIndice,
                'totalResults' => $totalResults
            ]
        );
    }

    public function filterData(Request $request)
    {
        if (isset($request->all()['datosFormulario'])) {
            $data = $request->all()['datosFormulario'];
        } else {
            $data = [];
        }

        $query = asignadas::select('*')
            ->whereIn('asignadas.tipo_trabajo', [10444, 12161])
            ->where('asignadas.status', 1);

        $arrayEstPro = [
            'Aplaza visita',
            'Llamar de nuevo',
            'No autoriza',
            'No contesta',
            'Programada',
        ];

        $causasCierre = TblCausasCierre::all();

        // CONSULTAMOS LOS INPSECTORES
        $inspectores = tbl_insp_cali::select('id', 'nombres', 'apellidos')
            ->where('state', 1)
            ->get();

        if (!empty($data)) {
            foreach ($data as $key => $value) {

                $flag = false;

                if ($key == 'contrato') {
                    $key = 'asignadas' . '.' . $key;
                }

                if ($key == 'id_sede') {
                    $query->leftJoin('tbl_localidades_municipios', 'tbl_localidades_municipios.nombre', '=', 'asignadas.localidad');
                    $key = 'tbl_localidades_municipios' . '.' . $key;
                }

                $arrayValues = [];
                $operator = 'IN';

                if ($key != 'dias') {
                    if (!is_array($value) && strpos($value, ',') !== false) {
                        $valueSeparate = explode(",", $value);
                        foreach ($valueSeparate as $value) {
                            $arrayValues[] = intval($value);
                        }
                    } else {
                        if (!is_array($value)) {
                            $arrayValues[] = intval($value);
                        } else if ($key != "localidad" && $key != "sector_operativo") {
                            foreach ($value as $val) {
                                $arrayValues[] = intval($val);
                            }
                        }
                    }
                }

                if ($key == 'datos') {

                    $arrayIdMun = [];
                    $arrayNomMun = [];
                    $queryDetails = TblGruposDetalle::select('id_mun');

                    $arrayIdGroup = [];
                    $arrayIdSubGroup = [];
                    $arrayIdSede = [];

                    if(isset($data['id_sede'])){
                        foreach($data['id_sede'] as $valueId){
                            $arrayIdSede[] = intval($valueId);
                        }
                    }


                    if (isset($value['id_grupo'])) {
                        foreach ($value['id_grupo'] as $valGroup) {
                            $arrayIdGroup[] = intval($valGroup);
                        }
                        $queryDetails->wherein('id_grupo', $arrayIdGroup);
                    }

                    if (isset($value['id_subGrupo'])) {
                        foreach ($value['id_subGrupo'] as $valSubGroup) {
                            $arrayIdSubGroup[] = intval($valSubGroup);
                        }
                        $queryDetails->wherein('id_subgrupo', $arrayIdSubGroup);
                    }
                    $dataDetail = $queryDetails->groupBy('id_mun')->get();

                    foreach ($dataDetail as $detail) {
                        $arrayIdMun[] = $detail->id_mun;
                    }

                    foreach ($arrayIdMun as $idMun) {
                        // consultamos el nombre del municipio con el id del municipio
                        $sqlMunQuery = tbl_localidades_municipio::where('id', $idMun)
                                                                ->whereIn('id_sede', $arrayIdSede);

                        // Ejecutar la consulta y obtener los resultados
                        $sqlMun = $sqlMunQuery->get();
                        $arrayNomMun[] = $sqlMun[0]->nombre;
                    }

                    $arrayValues = $arrayNomMun;

                    $key = "localidad";
                    $flag = true;
                }

                if (($key == 'localidad' || $key == 'sector_operativo') && !$flag) {
                    $operator = 'LIKE';
                    $values = ['%' . $value . '%'];
                }

                if ($key != 'dias') {
                    if ($operator === 'IN') {
                        $query->whereIn($key, $arrayValues);
                    } else {
                        $query->where($key, $operator, $values[0]);
                    }
                }

                if ($key == 'dias') {
                    $key = 'dias_ejecutar';

                    if (isset($value['dia_inicio']) && $value['dia_inicio'] != null) {
                        $dia_inicio = intval($value['dia_inicio']);
                    } else {
                        $dia_inicio = "";
                    }

                    if (isset($value['dia_fin']) && $value['dia_fin'] != null) {
                        $dia_fin = intval($value['dia_fin']);
                    } else {
                        $dia_fin = "";
                    }

                    if ($dia_inicio != "" && $dia_fin == "") {
                        // Condición para cuando dia_inicio es positivo y dia_fin llega vacío o no existe
                        $query->where($key, '<=', $dia_inicio);
                    } else if ($dia_inicio != ""  && $dia_fin != "") {
                        // Si los dos dias son positivos, debe traer los registros entre esos dias
                        $minValue = min($dia_inicio, $dia_fin);
                        $maxValue = max($dia_inicio, $dia_fin);
                        $query->whereBetween($key, [$minValue, $maxValue]);
                    }
                }
            }
        }

        $porPagina = 100;
        $pagina = $request->input('pagina', 1);
        $offset = ($pagina - 1) * $porPagina;

        $totalResults = $query->count();

        $datos = $query
            ->skip($offset)
            ->take($porPagina)
            ->get();

        $datosConIndice = $datos->map(function ($item, $index) use ($offset) {

            // consultamos la tabla de recepcion con el id de la orden para traer ele estado de recepcion y la fecha de recepcion
            $queryRecepcion = TblRecepcion::where('ordenTrabajo', $item->orden)->first();

            if ($queryRecepcion != null) {

                $estadoRecepcion = $queryRecepcion->estadoRecepcion;
                $fechaRecepcion = explode(" ", $queryRecepcion->created_at)[0];

                // consultamos la tabla detalle para traer el ultimo registro de cada orden y el total
                $queryDetalleVne = TblRecepcionVneDetalle::where('ordenTrabajo', $queryRecepcion->ordenTrabajo);
                $queryDetalleVne->orderBy('id', 'desc')->limit(1);
                $detalleRecepcion = $queryDetalleVne->get();

                if (isset($detalleRecepcion[0])) {
                    $totalVneOrden = $queryDetalleVne->count();

                    $fecha = explode(" ", $detalleRecepcion[0]->created_at)[0];
                    $timestamp = strtotime($fecha);
                    $fechaLegible = gmdate('d-M-y', $timestamp);
                    $fechaUltimaVne = strtolower($fechaLegible);
                    $ultimaVne = $detalleRecepcion[0]->idVne;

                    // consultamos los inspectores para sacar el id del ultimo inspector
                    $queryInspectores = tbl_insp_cali::where('cedula', $detalleRecepcion[0]->ccOperario)->first();

                    $inspectorUltimaVne = $queryInspectores->id;

                    $compiladoObservacion = $detalleRecepcion[0]->comObservacion;
                }
            } else {
                $totalVneOrden = "";
                $fechaUltimaVne = "";
                $ultimaVne = "";
                $inspectorUltimaVne = "";
                $compiladoObservacion = "";
                $fechaRecepcion = "";
                $estadoRecepcion = "";
            }

            if ($estadoRecepcion != "") {

                if ($item->tipo_trabajo == "12161") {
                    $columnaConsultar = "ORDEN_EXT";
                } else {
                    $columnaConsultar = "ORDEN_TRABAJO";
                }

                $contratoBuscar = ":" . $item->contrato;

                // consultamos la tabla de bitacoras
                $queryBitacora = tbl_bitacora_contrato::where($columnaConsultar, $item->orden)
                    ->where('CONTRATO', $contratoBuscar)
                    ->first();

                // consultamos en la tabla de devoluciones
                $queryDev = tbl_dv_insp::with('Supervisor')
                    ->where($columnaConsultar, $item->orden)
                    ->where('CONTRATO', $contratoBuscar)
                    ->first();

                if (($queryBitacora != null && $queryDev != null) || $queryBitacora != null) {
                    $numActa = $queryBitacora->No_ACTA;
                    $validacionFormato = "Aprobado";
                    $observacionRechazo = "";
                } else if ($queryDev != null) {
                    $numActa = $queryDev->No_ACTA;
                    $validacionFormato = "Rechazado";
                    $observacionRechazo = $queryDev->Supervisor->name . " " . $queryDev->FECHA_DV;
                }
            } else {
                $numActa = "";
                $validacionFormato = "";
                $observacionRechazo = "";
            }

            // consultamos la tabla de contratos con la orden
            $queryProCont = tbl_programacion_contrato::where('ORDEN_TRABAJO', $item->orden)->first();
            if ($queryProCont != null) {
                $jornada = explode(" ", $queryProCont->HORA_INICIO);

                $jornada = $jornada[1];
                $celular = $queryProCont->CELULAR;
                $observaciones = $queryProCont->OBSERVACIONES;
                $fechaAgendamiento = $queryProCont->FECHA_AGENDAMIENTO;
            } else {
                $jornada = "";
                $celular = "";
                $observaciones = "";
                $fechaAgendamiento = "";
            }

            if ($item->tipo_trabajo == "12161") {
                $diaIngreso = explode(" ", $item->created_at)[0];
                $tipoOrden = "Ext." . $item->tipo_trabajo;
            } else {
                if ($item->orden_solicitud_externa != null) {
                    $diaIngreso = $item->fecha_reasignacion_externa;
                    $tipoOrden = "Ext." . $item->tipo_solicitud_externa;
                } else {
                    $diaIngreso = explode(" ", $item->created_at)[0];
                    $tipoOrden = "Masiva";
                }
            }

            $queryMunicipio = tbl_localidades_municipio::where('nombre', $item->localidad)->first();

            // Obtenemos la sede directamente desde la consulta anterior
            $querySede = tbl_localidades_sede::where('id', $queryMunicipio->id_sede)->first();

            // Consultamos primero el municipio
            $queryLugar = $queryMunicipio;
            $columnaLugar = 'id_mun';

            // Si no se encuentra el detalle por municipio, buscamos por barrio
            $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();

            if ($queryDetalle == null) {
                // Ahora buscamos por barrio si no encontramos el detalle por municipio
                $queryLugar = TblBarrios::where('barrio', $item->sector_operativo)->first();
                $columnaLugar = 'id_barrio';

                if ($queryLugar != null) {
                    $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();
                }
            }
            // Consultamos la tabla de grupos
            $queryGrupos = TblGrupo::where('id', $queryDetalle->id_grupo)->first();
            // Consultamos la tabla de subgrupos
            $querySubGrupos = TblSubgrupo::where('id', $queryDetalle->id_subGrupo)->first();

            // CALCULO DE LOS MESES
            if ($item->fecha_ult_cert != '1970-01-01') {
                $fechaCertificacion = new DateTime($item->fecha_ult_cert); // Fecha de certificación
                $fechaActual = new DateTime(); // Fecha actual
                // Obtener la diferencia entre las dos fechas
                $diferencia = $fechaCertificacion->diff($fechaActual);
                // Calcular los meses transcurridos
                $mesesTranscurridos = ($diferencia->y * 12) + $diferencia->m;
                // Verificar si los días adicionales suman un mes más
                if ($diferencia->d > 0) {
                    $mesesTranscurridos++;
                }
            } else {
                $mesesTranscurridos = 60;
            }

            if ($item->dias_ejecutar < 0) {
                $cumplePoliticas = "NO";
            } else {
                $cumplePoliticas = "SI";
            }

            if (strpos($item->estado_corte, "CONEXION")) {
                $substr = 14;
            } else if (strpos($item->estado_corte, "ORDEN")) {
                $substr = 31;
            } else if (strpos($item->estado_corte, "ORDEN DE CONEXION")) {
                $substr = 21;
            } else {
                $substr = 22;
            }

            $cartera = substr($item->estado_corte, 0, $substr);

            // FECHA VENCE CERTIFICADO
            $fechaVence = strtotime($item->vence);
            $fechaVence = gmdate('d-M-y', $fechaVence);
            $fechaVence = strtolower($fechaVence);

            $fechaUltCert = strtotime($item->fecha_ult_cert);
            $fechaUltCert = gmdate('d-M-y', $fechaUltCert);
            $fechaUltCert = strtolower($fechaUltCert);

            // Estados gestion
            // En campo
            if ($item->codigo_tecnico != null) {
                //consultamos la tabla programacion contratos con el contrato
                $queryProgramacionContrato = tbl_programacion_contrato::where('CONTRATO', $item->contrato)->first();
                // consultamos la tabla de recepcion vne detalle para saber si la orden tiene volantes
                $queryVneDetalle = TblRecepcionVneDetalle::where('ordenTrabajo', $item->orden)->first();

                if ($queryProgramacionContrato == null && $queryVneDetalle == null) {
                    $estadoGestion = "En campo";
                }

                // En campo con volantes previos
                if ($queryVneDetalle != null) {
                    $estadoGestion = "En campo con volantes previos";
                }

                // En campo con programacion incumplida
                if ($queryProgramacionContrato != null && $queryVneDetalle == null && $item->estado_programacion == null) {
                    $estadoGestion = "En campo con programacion incumplida";
                }

                //En campo con programacion a futuro
                if ($queryProgramacionContrato != null && $queryProgramacionContrato->FECHA_AGENDAMIENTO > $fechaActual->format('Y-m-d')) {
                    $estadoGestion = "En campo con programacion a futuro";
                }
            } else {
                // validamos si es masiva o externa
                if ($item->orden_solicitud_externa != null || $item->tipo_trabajo == "12161") {
                    $estadoGestion = "Externa sin asignar";
                } else {
                    $estadoGestion = "Masiva sin asignar";
                }
            }
            //--------------

            // Ejecutada efectiva por legalizar
            if ($item->estado_programacion != null && $item->status == 1) {
                $estadoGestion == "Ejecutada efectiva por legalizar";
            }
            //--------------------------------

            //Pendiente anulacion
            if ($item->causa_cierre != null) {
                $estadoGestion = "Pendiente anulacion";
            }

            //Validar suspendido por cartera
            if (strpos($item->estado_corte, "3") !== false || strpos($item->estado_corte, "5") !== false) {
                $estadoGestion = "Validar suspendido por cartera";
            }

            return [
                'indice' => $index + 1 + $offset,
                'orden' => $item->orden,
                'contrato' => $item->contrato,
                'producto' => $item->producto,
                // 1. ASIGNACION BASE OSF
                'numero_solicitud' => $item->numero_solicitud,
                'tipo_solicitud' => $item->tipo_solicitud,
                'NIT_CC' => $item->NIT_CC,
                'nombre_lugar' => $item->nombre_lugar,
                'departamento' => $item->departamento,
                'localidad' => $item->localidad,
                'sector_operativo' => $item->sector_operativo,
                'direccion' => $item->direccion,
                'consecutivo_ruta' => $item->consecutivo_ruta,
                'telefono' => $item->telefono,
                'medidor' => $item->medidor,
                'categoria' => $item->categoria,
                'unidad_operativa' => $item->unidad_operativa,
                'tipo_trabajo' => $item->tipo_trabajo,
                'fecha_asignacion' => $item->fecha_asignacion,
                'observacion_solicitud' => $item->observacion_solicitud,
                // 2. INFORMACIÓN COMPLEMENTARIA 12161
                'orden_solicitud_externa' => $item->orden_solicitud_externa,
                'tipo_solicitud_externa' => $item->tipo_solicitud_externa,
                'fecha_solicitud_externa' => $item->fecha_solicitud_externa,
                'observacion_externa' => $item->observacion_externa,
                'fecha_reasignacion_externa' => $item->fecha_reasignacion_externa,
                // 3. PROGRAMACIÓN DE ORDENES
                'FECHA_AGENDAMIENTO' => $fechaAgendamiento,
                'jornada' => $jornada,
                'CELULAR' => $celular,
                'OBSERVACIONES' => $observaciones,
                'estado_programacion' => $item->estado_programacion,
                // 4. ASIGNACIÓN INSPECTOR
                'codigo_tecnico' => $item->codigo_tecnico,
                'fecha_asignacion_inspector' => $item->fecha_asignacion_inspector,
                // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
                'estado_recepcion' => $estadoRecepcion,
                'fecha_recepcion' => $fechaRecepcion,
                'cantidad_vne' => $totalVneOrden,
                'ultima_vne' => $ultimaVne,
                'fecha_ultima_vne' => $fechaUltimaVne,
                'inspector_ultima_vne' => $inspectorUltimaVne,
                'compilado_observacion' => $compiladoObservacion,
                'causa_cierre' => $item->causa_cierre,
                'fecha_solicitud_cierre' => $item->fecha_solicitud_cierre,
                // 6.GESTIÓN REALIZADA OFICINA
                'num_acta' => $numActa,
                'validacion_formato' => $validacionFormato,
                'observacion_rechazo' => $observacionRechazo,
                // 7. FORMULACIÓN Y CALCULO
                'dia_ingreso' => $diaIngreso,
                'tipo_orden' => $tipoOrden,
                'sede' => $querySede->nombre,
                'grupo' => $queryGrupos->grupo,
                'subgrupo' => $querySubGrupos->subgrupo,
                'meses' => $mesesTranscurridos,
                'fecha_vence_certificado' => $fechaVence,
                'dias_ejecutar' => $item->dias_ejecutar,
                'cumplimiento_politicas' => $cumplePoliticas,
                'cartera' => $cartera,
                'consumo' => $item->estado_producto,
                'fecha_ult_cert' => $fechaUltCert,
                'estado_gestion' => $estadoGestion,
                'ult_comentario' => $item->ult_comentario,
                'nom_inspector' => $item->nom_inspector,
                'marca' => $item->marca
            ];
        });

        return response()->json(
            [
                'estadoProgramacion' => $arrayEstPro,
                'inspectores' => $inspectores,
                'causasCierre' => $causasCierre,
                'data' => $datosConIndice,
                'totalResults' => $totalResults
            ]
        );
    }

    public function guardarProgramacionTecnico(Request $request)
    {
        $codigoTecnico = intval($request->input('codigoTecnico'));
        $orden = intval($request->input('ordenEnviar'));
        $estadoProgramacion = $request->input('estado');

        $campoActualizar = "";
        $valorActualizar = "";
        $fechaActual = "";
        $campoFecha = "";
        $parametros = [];

        if ($codigoTecnico != NULL) {

            // consultamos el nombre del ispector con el  codigo
            $queryInspector = tbl_insp_cali::where('id', $codigoTecnico)->first();
            if ($queryInspector != null) {
                $nombreInspector = $queryInspector->apellidos . " " . $queryInspector->nombres;
            } else {
                $nombreInspector = "";
            }

            $fechaActual = date('Y-m-d');
            $campoFecha = ", fecha_asignacion_inspector = ?";
            $campoActualizar = "codigo_tecnico";
            $campoNombre = "nom_inspector";
            $valorActualizar = $codigoTecnico;

            $tecnico = DB::table('tbl_insp_cali')->where('id', $codigoTecnico)->first();

            if ($tecnico == null) {
                echo 3;
                exit;
            }
            $parametros = [$valorActualizar, $nombreInspector, $fechaActual, $orden];
        } else if ($estadoProgramacion != null) {
            $campoActualizar = "estado_programacion";
            $valorActualizar = $estadoProgramacion;
            $campoNombre = "nom_inspector";
            $parametros = [$valorActualizar, null, $orden];
        }

        $asignadas = DB::update(
            "UPDATE asignadas
                    SET {$campoActualizar} = ?,
                    {$campoNombre} = ?
                    {$campoFecha}
                    WHERE orden = ?",
            $parametros
        );

        if ($asignadas) {
            echo 1;
        } else {
            echo 2;
        }
    }

    public function getGroupsForSede(Request $request)
    {

        $idSedes = $request['idSede'];

        $dataSeparated = explode(",", $idSedes);

        $arrayIdSede = [];
        foreach ($dataSeparated as $val) {
            $arrayIdSede[] = intval($val);
        }

        // consultamos los municipios con el id de las sedes
        $sqlTblMun = tbl_localidades_municipio::select('id')->whereIn('id_sede', $arrayIdSede)->get();

        // con el id del municipio consultamos los grupos a los que pertenece ese municipio
        // sacamos el id de los municipios
        $arrayIdGroup = [];
        foreach ($sqlTblMun as $idmun) {
            $sqlGroupDetail = TblGruposDetalle::where('id_mun', $idmun->id)->get();
            foreach ($sqlGroupDetail as $detail) {
                $arrayIdGroup[] = $detail->id_grupo;
            }
        }

        $arrayIdGroup = array_unique($arrayIdGroup);

        // consultamos los grupos con los id de los grupos
        $sqlGroups = TblGrupo::wherein('id', $arrayIdGroup)->get();

        return response()->json([
            'grupos' => $sqlGroups,
            'tipo' => 1
        ]);
    }

    public function getDataSubGroups(Request $request)
    {

        $idGrupo = $request['idGrupo'];

        $dataSeparated = explode(",", $idGrupo);

        $arrayIdGrupo = [];
        foreach ($dataSeparated as $val) {
            $arrayIdGrupo[] = intval($val);
        }

        // consultamos la tabla de detalles para traer los subgrupos que le pertenezcan a los id de los grupos
        $sqlSubGrupo = TblGruposDetalle::whereIn('id_grupo', $arrayIdGrupo)->get();

        $arrayIdSubgrupo = [];
        foreach ($sqlSubGrupo as $subGrupo) {
            $arrayIdSubgrupo[] = $subGrupo->id_subGrupo;
        }

        $arrayIdSubgrupo = array_unique($arrayIdSubgrupo);

        // con los ids del subgrupo consultamos la tabla de subgrupos
        $sqlSubgrupos = TblSubgrupo::whereIn('id', $arrayIdSubgrupo)->get();

        return response()->json([
            'subGrupo' => $sqlSubgrupos,
            'tipo' => 2
        ]);
    }

    public function descargarExcelCoordinacion()
    {

        require '../vendor/autoload.php';

        set_time_limit(400);
        ini_set('memory_limit', '1024M');

        $spread = new Spreadsheet();

        // Agregar datos al archivo Excel
        $sheet = $spread->getActiveSheet();
        $sheet->setTitle("Coordinacion RP");

        // COMBINAMOS LAS CELDAS NECESARIAS
        $sheet->mergeCells('A1:S1');
        $sheet->mergeCells('T1:X1');
        $sheet->mergeCells('Y1:AC1');
        $sheet->mergeCells('AD1:AE1');
        $sheet->mergeCells('AF1:AN1');
        $sheet->mergeCells('AO1:AQ1');
        $sheet->mergeCells('AR1:BH1');

        $sheet->freezePane('E1');

        $arrayHeader = [
            "A1" => "1. ASIGNACION BASE OSF",
            "T1" => "2. INFORMACIÓN COMPLEMENTARIA 12161",
            "Y1" => "3. PROGRAMACIÓN DE ORDENES",
            "AD1" => "4. ASIGNACIÓN INSPECTOR",
            "AF1" => "5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO",
            "AO1" => "6. GESTIÓN REALIZADA OFICINA",
            "AR1" => "7. FORMULACIÓN Y CALCULO",
            "A2" => "Orden",
            "B2" => "Contrato",
            "C2" => "Producto",
            "D2" => "Numero solicitud",
            "E2" => "Tipo solicitud",
            "F2" => "Cedula",
            "G2" => "Nombre",
            "H2" => "Departamento",
            "I2" => "Localidad",
            "J2" => "Barrio",
            "K2" => "Dirección",
            "L2" => "Consecutivo Ruta",
            "M2" => "Telefono",
            "N2" => "Medidor",
            "O2" => "Categoria",
            "P2" => "Unidad",
            "Q2" => "Tipo trabajo",
            "R2" => "Fecha asignación",
            "S2" => "Observación solicitud",
            "T2" => "Orden externa",
            "U2" => "Tipo solicitud",
            "V2" => "Fecha solicitud",
            "W2" => "Observación externa",
            "X2" => "Fecha reasignación",
            "Y2" => "Fecha programación",
            "Z2" => "Jornada",
            "AA2" => "Telefono usuario",
            "AB2" => "Descripción programacion",
            "AC2" => "Estado programación",
            "AD2" => "Asignación inspector",
            "AE2" => "Fecha asignación inspector",
            "AF2" => "Estado recepción",
            "AG2" => "Fecha recepción",
            "AH2" => "#VNE",
            "AI2" => "Estado ultima VNE",
            "AJ2" => "Fecha ultima VNE",
            "AK2" => "Inspector ultima VNE",
            "AL2" => "Compilado observacion",
            "AM2" => "Causa cierre",
            "AN2" => "Fecha solicitud de cierre",
            "AO2" => "Acta real",
            "AP2" => "Validación formato",
            "AQ2" => "Observacion rechazo",
            "AR2" => "Día ingreso",
            "AS2" => "Tipo orden",
            "AT2" => "Sede",
            "AU2" => "Grupo",
            "AV2" => "Sub grupo",
            "AW2" => "Meses",
            "AX2" => "Fecha vence certificado",
            "AY2" => "Días para ejecutar",
            "AZ2" => "Cumplimiento politicas",
            "BA2" => "Cartera",
            "BB2" => "Consumo",
            "BC2" => "Fecha ultimo certificado",
            "BD2" => "Estado gestion",
            "BE2" => "Observación OSF",
            "BF2" => "Nombre inspector",
            "BG2" => "Días gestion actual",
            "BH2" => "Fecha actual",
        ];

        foreach ($arrayHeader as $key => $header) {

            // asignamos el rango de columnas a lque queremos aplicar el color asigado en $color
            if ($key == "A1") {
                $color = 'FFC4D79B';
                $rango = 'A1:S2';
            } else if ($key == "T1") {
                $color = 'FFB1A0C7';
                $rango = 'T1:X2';
            } else if ($key == "Y1") {
                $color = 'FFFABF8F';
                $rango = 'Y1:AC2';
            } else if ($key == "AD1") {
                $color = 'FF95B3D7';
                $rango = 'AD1:AE2';
            } else if ($key == "AF1") {
                $color = 'FFC0504D';
                $rango = 'AF1:AN2';
            } else if ($key == "AO1") {
                $color = 'FF8064A2';
                $rango = 'AO1:AQ2';
            }else if($key == "AR1"){
                $color = 'FF92CDDC';
                $rango = 'AR1:BH2';
            }

            // asigamos el estilo al rango de celdas
            $sheet->getStyle($rango)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => $color],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            // asognamos los demas encabezados
            $sheet->setCellValue($key, $header);

            // sacamos solo la letra de la columna para dimensionar la columna
            if (strpos($key, "1")) {
                $key = explode("1", $key)[0];
            } else {
                $key = explode("2", $key)[0];
            }

            if ($key === 'S' || $key === 'BE') {
                $sheet->getColumnDimension($key)->setWidth(70); // Ancho personalizado
                $sheet->getColumnDimension($key)->setWidth(70); // Ancho personalizado
            } else {
                // Ancho automático para las demás columnas
                $sheet->getColumnDimension($key)->setAutoSize(true);
            }
        }

        // Obtener los datos necesarios
        $query = asignadas::select('*')
            ->whereIn('asignadas.tipo_trabajo', [10444, 12161])
            ->where('status', 1)
            ->get();

        // Crear un array para almacenar los datos con índice
        $datos = $query->map(function ($item) {

            $queryRecepcion = TblRecepcion::where('ordenTrabajo', $item->orden)->first();

            if ($queryRecepcion != null) {

                $estadoRecepcion = $queryRecepcion->estadoRecepcion;
                $fechaRecepcion = explode(" ", $queryRecepcion->created_at)[0];

                // consultamos la tabla detalle para traer el ultimo registro de cada orden y el total
                $queryDetalleVne = TblRecepcionVneDetalle::where('ordenTrabajo', $queryRecepcion->ordenTrabajo);
                $queryDetalleVne->orderBy('id', 'desc')->limit(1);
                $detalleRecepcion = $queryDetalleVne->get();

                if (isset($detalleRecepcion[0])) {
                    $totalVneOrden = $queryDetalleVne->count();

                    $fecha = explode(" ", $detalleRecepcion[0]->created_at)[0];
                    $timestamp = strtotime($fecha);
                    $fechaLegible = gmdate('d-M-y', $timestamp);
                    $fechaUltimaVne = strtolower($fechaLegible);
                    $ultimaVne = $detalleRecepcion[0]->idVne;

                    // consultamos los inspectores para sacar el id del ultimo inspector
                    $queryInspectores = tbl_insp_cali::where('cedula', $detalleRecepcion[0]->ccOperario)->first();

                    $inspectorUltimaVne = $queryInspectores->id;

                    $compiladoObservacion = $detalleRecepcion[0]->comObservacion;
                }
            } else {
                $totalVneOrden = "";
                $fechaUltimaVne = "";
                $ultimaVne = "";
                $inspectorUltimaVne = "";
                $compiladoObservacion = "";
                $fechaRecepcion = "";
                $estadoRecepcion = "";
            }

            if ($estadoRecepcion != "") {

                if ($item->tipo_trabajo == "12161") {
                    $columnaConsultar = "ORDEN_EXT";
                } else {
                    $columnaConsultar = "ORDEN_TRABAJO";
                }

                $contratoBuscar = ":" . $item->contrato;

                // consultamos la tabla de bitacoras
                $queryBitacora = tbl_bitacora_contrato::where($columnaConsultar, $item->orden)
                    ->where('CONTRATO', $contratoBuscar)
                    ->first();

                // consultamos en la tabla de devoluciones
                $queryDev = tbl_dv_insp::with('Supervisor')
                    ->where($columnaConsultar, $item->orden)
                    ->where('CONTRATO', $contratoBuscar)
                    ->first();

                if (($queryBitacora != null && $queryDev != null) || $queryBitacora != null) {
                    $numActa = $queryBitacora->No_ACTA;
                    $validacionFormato = "Aprobado";
                    $observacionRechazo = "";
                } else if ($queryDev != null) {
                    $numActa = $queryDev->No_ACTA;
                    $validacionFormato = "Rechazado";
                    $observacionRechazo = $queryDev->Supervisor->name . " " . $queryDev->FECHA_DV;
                }
            } else {
                $numActa = "";
                $validacionFormato = "";
                $observacionRechazo = "";
            }

            // consultamos la tabla de contratos con la orden
            $queryProCont = tbl_programacion_contrato::where('ORDEN_TRABAJO', $item->orden)->first();
            if ($queryProCont != null) {
                $jornada = explode(" ", $queryProCont->HORA_INICIO);

                $jornada = $jornada[1];
                $celular = $queryProCont->CELULAR;
                $observaciones = $queryProCont->OBSERVACIONES;
                $fechaAgendamiento = $queryProCont->FECHA_AGENDAMIENTO;
            } else {
                $jornada = "";
                $celular = "";
                $observaciones = "";
                $fechaAgendamiento = "";
            }

            if ($item->tipo_trabajo == "12161") {
                $diaIngreso = explode(" ", $item->created_at)[0];
                $tipoOrden = "Ext." . $item->tipo_trabajo;
            } else {
                if ($item->orden_solicitud_externa != null) {
                    $diaIngreso = $item->fecha_reasignacion_externa;
                    $tipoOrden = "Ext." . $item->tipo_solicitud_externa;
                } else {
                    $diaIngreso = explode(" ", $item->created_at)[0];
                    $tipoOrden = "Masiva";
                }
            }

            $queryMunicipio = tbl_localidades_municipio::where('nombre', $item->localidad)->first();

            // Obtenemos la sede directamente desde la consulta anterior
            $querySede = tbl_localidades_sede::where('id', $queryMunicipio->id_sede)->first();

            // Consultamos el barrio
            $queryLugar = TblBarrios::where('barrio', $item->sector_operativo)->first();
            $columnaLugar = 'id_barrio';

            // Si no se encuentra el barrio, usamos el municipio consultado previamente
            if ($queryLugar == null) {
                $queryLugar = $queryMunicipio; // Reutilizamos el resultado ya consultado
                $columnaLugar = 'id_mun';
            }
            // Consultamos el detalle del grupo
            $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();

            // Consultamos la tabla de grupos
            $queryGrupos = TblGrupo::where('id', $queryDetalle->id_grupo)->first();
            // Consultamos la tabla de subgrupos
            $querySubGrupos = TblSubgrupo::where('id', $queryDetalle->id_subGrupo)->first();

            // CALCULO DE LOS MESES
            if ($item->fecha_ult_cert != '1970-01-01') {
                $fechaCertificacion = new DateTime($item->fecha_ult_cert); // Fecha de certificación
                $fechaActual = new DateTime(); // Fecha actual
                // Obtener la diferencia entre las dos fechas
                $diferencia = $fechaCertificacion->diff($fechaActual);
                // Calcular los meses transcurridos
                $mesesTranscurridos = ($diferencia->y * 12) + $diferencia->m;
                // Verificar si los días adicionales suman un mes más
                if ($diferencia->d > 0) {
                    $mesesTranscurridos++;
                }
            } else {
                $mesesTranscurridos = 60;
            }

            if ($item->dias_ejecutar < 0) {
                $cumplePoliticas = "NO";
            } else {
                $cumplePoliticas = "SI";
            }

            if (strpos($item->estado_corte, "CONEXION")) {
                $substr = 14;
            } else if (strpos($item->estado_corte, "ORDEN")) {
                $substr = 31;
            } else if (strpos($item->estado_corte, "ORDEN DE CONEXION")) {
                $substr = 21;
            } else {
                $substr = 22;
            }

            $cartera = substr($item->estado_corte, 0, $substr);

            // FECHA VENCE CERTIFICADO
            $fechaVence = strtotime($item->vence);
            $fechaVence = gmdate('d-M-y', $fechaVence);
            $fechaVence = strtolower($fechaVence);

            $fechaUltCert = strtotime($item->fecha_ult_cert);
            $fechaUltCert = gmdate('d-M-y', $fechaUltCert);
            $fechaUltCert = strtolower($fechaUltCert);

            // Estados gestion
            // En campo
            if ($item->codigo_tecnico != null) {
                //consultamos la tabla programacion contratos con el contrato
                $queryProgramacionContrato = tbl_programacion_contrato::where('CONTRATO', $item->contrato)->first();
                // consultamos la tabla de recepcion vne detalle para saber si la orden tiene volantes
                $queryVneDetalle = TblRecepcionVneDetalle::where('ordenTrabajo', $item->orden)->first();

                if ($queryProgramacionContrato == null && $queryVneDetalle == null) {
                    $estadoGestion = "En campo";
                }

                // En campo con volantes previos
                if ($queryVneDetalle != null) {
                    $estadoGestion = "En campo con volantes previos";
                }

                // En campo con programacion incumplida
                if ($queryProgramacionContrato != null && $queryVneDetalle == null && $item->estado_programacion == null) {
                    $estadoGestion = "En campo con programacion incumplida";
                }

                //En campo con programacion a futuro
                if ($queryProgramacionContrato != null && $queryProgramacionContrato->FECHA_AGENDAMIENTO > $fechaActual->format('Y-m-d')) {
                    $estadoGestion = "En campo con programacion a futuro";
                }
            } else {
                // validamos si es masiva o externa
                if ($item->orden_solicitud_externa != null || $item->tipo_trabajo == "12161") {
                    $estadoGestion = "Externa sin asignar";
                } else {
                    $estadoGestion = "Masiva sin asignar";
                }
            }
            //--------------

            // Ejecutada efectiva por legalizar
            if ($item->estado_programacion != null && $item->status == 1) {
                $estadoGestion == "Ejecutada efectiva por legalizar";
            }
            //--------------------------------

            //Pendiente anulacion
            if ($item->causa_cierre != null) {
                $estadoGestion = "Pendiente anulacion";
            }

            //Validar suspendido por cartera
            if (strpos($item->estado_corte, "3") !== false || strpos($item->estado_corte, "5") !== false) {
                $estadoGestion = "Validar suspendido por cartera";
            }

            $fechaActual = new DateTime();

            if ($item->fecha_asignacion_inspector != null) {
                $fechaAsignacion = new DateTime($item->fecha_asignacion_inspector);

                $diferencia = $fechaActual->diff($fechaAsignacion);

                $diferenciaDias = $diferencia->days;
            } else {
                $diferenciaDias = "";
            }

            return [
                'A' => $item->orden,
                'B' => $item->contrato,
                'C' => $item->producto,
                'D' => $item->numero_solicitud,
                'E' => $item->tipo_solicitud,
                'F' => $item->NIT_CC,
                'G' => $item->nombre_lugar,
                'H' => $item->departamento,
                'I' => $item->localidad,
                'J' => $item->sector_operativo,
                'K' => $item->direccion,
                'L' => $item->consecutivo_ruta,
                'M' => $item->telefono,
                'N' => $item->medidor,
                'O' => $item->categoria,
                'P' => $item->unidad_operativa,
                'Q' => $item->tipo_trabajo,
                'R' => $item->fecha_asignacion,
                'S' => $item->observacion_solicitud,
                'T' => $item->orden_solicitud_externa,
                'U' => $item->tipo_solicitud_externa,
                'V' => $item->fecha_solicitud_externa,
                'W' => $item->observacion_externa,
                'X' => $item->fecha_reasignacion_externa,
                'Y' => $fechaAgendamiento,
                'Z' => $jornada,
                'AA' => $celular,
                'AB' => $observaciones,
                'AC' => $item->estado_programacion,
                'AD' => $item->codigo_tecnico,
                'AE' => $item->fecha_asignacion_inspector,
                'AF' => $estadoRecepcion,
                'AG' => $fechaRecepcion,
                'AH' => $totalVneOrden,
                'AI' => $ultimaVne,
                'AJ' => $fechaUltimaVne,
                'AK' => $inspectorUltimaVne,
                'AL' => $compiladoObservacion,
                'AM' => $item->causa_cierre,
                'AN' => $item->fecha_solicitud_cierre,
                'AO' => $numActa,
                'AP' => $validacionFormato,
                'AQ' => $observacionRechazo,
                'AR' => $diaIngreso,
                'AS' => $tipoOrden,
                'AT' => $querySede->nombre,
                'AU' => $queryGrupos->grupo,
                'AV' => $querySubGrupos->subgrupo,
                'AW' => $mesesTranscurridos,
                'AX' => $fechaVence,
                'AY' => $item->dias_ejecutar,
                'AZ' => $cumplePoliticas,
                'BA' => $cartera,
                'BB' => $item->estado_producto,
                'BC' => $fechaUltCert,
                'BD' => $estadoGestion,
                'BE' => $item->ult_comentario,
                'BF' => $item->nom_inspector,
                'BG' => $diferenciaDias,
                'BH' => $fechaActual->format('Y-m-d')
            ];
        });

        $fila = 3;
        foreach ($datos as $values) {
            foreach ($values as $key => $val) {
                if ($key == "N") {
                    $sheet->setCellValueExplicit($key . $fila, $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($key . $fila, $val);
                }
            }
            $fila++;
        }
        // borramos los datos de la consulta para liberar memoria
        unset($datos);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spread, 'Xlsx');

        // Configurar las cabeceras para descargar el archivo
        $tempFile = tempnam(sys_get_temp_dir(), 'reporte');
        $writer->save($tempFile);

        // Enviar el archivo al cliente
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Reporte_coordinacion_rp.xlsx"');
        header('Cache-Control: max-age=0');
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }

    public function guardarCausaCierre(Request $request)
    {
        $orden = intVal($request->input('ordenEnviar'));
        $causaCierre = $request->input('causaCierre');

        if ($causaCierre == "Seleccione...") {
            $causaCierre = null;
        } else {
            $causaCierre = intVal(explode("-", $causaCierre)[0]);
        }

        // consultamos la tabla de asignadas con el numero de orden y actualizamos la columna
        $queryAsignadas = asignadas::where('orden', $orden)->first();

        $queryAsignadas->update([
            'causa_cierre' => $causaCierre
        ]);

        if ($queryAsignadas) {
            echo 1;
        } else {
            echo 2;
        }
    }

    public function guardarFechaSolicitudCierre(Request $request)
    {

        $orden = intVal($request->input('ordenEnviar'));
        $fechaSolicitudCierre = $request->input('fechaSolicitudCierre');

        // consultamos la tabla de asignadas con el numero de orden y actualizamos la columna
        $queryAsignadas = asignadas::where('orden', $orden)->first();

        $queryAsignadas->update([
            'fecha_solicitud_cierre' => $fechaSolicitudCierre
        ]);

        if ($queryAsignadas) {
            echo 1;
        } else {
            echo 2;
        }
    }

    public function historico()
    {
        $inspectors = tbl_insp_cali::all();
        return view('seguimiento.historico', compact('inspectors'));
    }

    public function getDataHistorico(Request $request)
    {

        $porPagina = 100; // Cantidad de registros por página
        $pagina = $request->input('pagina', 1); // Obtener el número de página de la solicitud
        $offset = ($pagina - 1) * $porPagina;

        // Obtener los datos necesarios
        $query = asignadas::select("*")
            ->whereIn('asignadas.tipo_trabajo', [10444, 12161])
            ->where('status', 0);

        $totalResults = $query->count();

        $query->skip($offset);
        $query->take($porPagina);
        $datos = $query->get();

        $datosConIndice = $datos->map(function ($item, $index) use ($offset) {

            $queryRecepcion = TblRecepcion::where('ordenTrabajo', $item->orden)->first();

            if ($queryRecepcion != null) {

                $estadoRecepcion = $queryRecepcion->estadoRecepcion;
                $fechaRecepcion = explode(" ", $queryRecepcion->created_at)[0];

                // consultamos la tabla detalle para traer el ultimo registro de cada orden y el total
                $queryDetalleVne = TblRecepcionVneDetalle::where('ordenTrabajo', $queryRecepcion->ordenTrabajo);
                $queryDetalleVne->orderBy('id', 'desc')->limit(1);
                $detalleRecepcion = $queryDetalleVne->get();

                if (isset($detalleRecepcion[0])) {
                    $totalVneOrden = $queryDetalleVne->count();

                    $fecha = explode(" ", $detalleRecepcion[0]->created_at)[0];
                    $timestamp = strtotime($fecha);
                    $fechaLegible = gmdate('d-M-y', $timestamp);
                    $fechaUltimaVne = strtolower($fechaLegible);
                    $ultimaVne = $detalleRecepcion[0]->idVne;

                    // consultamos los inspectores para sacar el id del ultimo inspector
                    $queryInspectores = tbl_insp_cali::where('cedula', $detalleRecepcion[0]->ccOperario)->first();

                    $inspectorUltimaVne = $queryInspectores->id;

                    $compiladoObservacion = $detalleRecepcion[0]->comObservacion;
                }
            } else {
                $totalVneOrden = "";
                $fechaUltimaVne = "";
                $ultimaVne = "";
                $inspectorUltimaVne = "";
                $compiladoObservacion = "";
                $fechaRecepcion = "";
                $estadoRecepcion = "";
            }

            if ($estadoRecepcion != "") {

                if ($item->tipo_trabajo == "12161") {
                    $columnaConsultar = "ORDEN_EXT";
                } else {
                    $columnaConsultar = "ORDEN_TRABAJO";
                }

                $contratoBuscar = ":" . $item->contrato;

                // consultamos la tabla de bitacoras
                $queryBitacora = tbl_bitacora_contrato::where($columnaConsultar, $item->orden)
                    ->where('CONTRATO', $contratoBuscar)
                    ->first();

                // consultamos en la tabla de devoluciones
                $queryDev = tbl_dv_insp::with('Supervisor')
                    ->where($columnaConsultar, $item->orden)
                    ->where('CONTRATO', $contratoBuscar)
                    ->first();

                if (($queryBitacora != null && $queryDev != null) || $queryBitacora != null) {
                    $numActa = $queryBitacora->No_ACTA;
                    $validacionFormato = "Aprobado";
                    $observacionRechazo = "";
                } else if ($queryDev != null) {
                    $numActa = $queryDev->No_ACTA;
                    $validacionFormato = "Rechazado";
                    $observacionRechazo = $queryDev->Supervisor->name . " " . $queryDev->FECHA_DV;
                }
            } else {
                $numActa = "";
                $validacionFormato = "";
                $observacionRechazo = "";
            }

            // consultamos la tabla de contratos con la orden
            $queryProCont = tbl_programacion_contrato::where('ORDEN_TRABAJO', $item->orden)->first();
            if ($queryProCont != null) {
                $jornada = explode(" ", $queryProCont->HORA_INICIO);

                $jornada = $jornada[1];
                $celular = $queryProCont->CELULAR;
                $observaciones = $queryProCont->OBSERVACIONES;
                $fechaAgendamiento = $queryProCont->FECHA_AGENDAMIENTO;
            } else {
                $jornada = "";
                $celular = "";
                $observaciones = "";
                $fechaAgendamiento = "";
            }

            if ($item->tipo_trabajo == "12161") {
                $diaIngreso = explode(" ", $item->created_at)[0];
                $tipoOrden = "Ext." . $item->tipo_trabajo;
            } else {
                if ($item->orden_solicitud_externa != null) {
                    $diaIngreso = $item->fecha_reasignacion_externa;
                    $tipoOrden = "Ext." . $item->tipo_solicitud_externa;
                } else {
                    $diaIngreso = explode(" ", $item->created_at)[0];
                    $tipoOrden = "Masiva";
                }
            }

            $queryMunicipio = tbl_localidades_municipio::where('nombre', $item->localidad)->first();

            // Obtenemos la sede directamente desde la consulta anterior
            $querySede = tbl_localidades_sede::where('id', $queryMunicipio->id_sede)->first();

            // Consultamos el barrio
            $queryLugar = TblBarrios::where('barrio', $item->sector_operativo)->first();
            $columnaLugar = 'id_barrio';

            // Si no se encuentra el barrio, usamos el municipio consultado previamente
            if ($queryLugar == null) {
                $queryLugar = $queryMunicipio; // Reutilizamos el resultado ya consultado
                $columnaLugar = 'id_mun';
            }
            // Consultamos el detalle del grupo
            $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();

            // Consultamos la tabla de grupos
            $queryGrupos = TblGrupo::where('id', $queryDetalle->id_grupo)->first();
            // Consultamos la tabla de subgrupos
            $querySubGrupos = TblSubgrupo::where('id', $queryDetalle->id_subGrupo)->first();

            // CALCULO DE LOS MESES
            if ($item->fecha_ult_cert != '1970-01-01') {
                $fechaCertificacion = new DateTime($item->fecha_ult_cert); // Fecha de certificación
                $fechaActual = new DateTime(); // Fecha actual
                // Obtener la diferencia entre las dos fechas
                $diferencia = $fechaCertificacion->diff($fechaActual);
                // Calcular los meses transcurridos
                $mesesTranscurridos = ($diferencia->y * 12) + $diferencia->m;
                // Verificar si los días adicionales suman un mes más
                if ($diferencia->d > 0) {
                    $mesesTranscurridos++;
                }
            } else {
                $mesesTranscurridos = 60;
            }

            if ($item->dias_ejecutar < 0) {
                $cumplePoliticas = "NO";
            } else {
                $cumplePoliticas = "SI";
            }

            if (strpos($item->estado_corte, "CONEXION")) {
                $substr = 14;
            } else if (strpos($item->estado_corte, "ORDEN")) {
                $substr = 31;
            } else if (strpos($item->estado_corte, "ORDEN DE CONEXION")) {
                $substr = 21;
            } else {
                $substr = 22;
            }

            $cartera = substr($item->estado_corte, 0, $substr);

            // FECHA VENCE CERTIFICADO
            $fechaVence = strtotime($item->vence);
            $fechaVence = gmdate('d-M-y', $fechaVence);
            $fechaVence = strtolower($fechaVence);

            $fechaUltCert = strtotime($item->fecha_ult_cert);
            $fechaUltCert = gmdate('d-M-y', $fechaUltCert);
            $fechaUltCert = strtolower($fechaUltCert);

            // Estados gestion
            // En campo
            if ($item->codigo_tecnico != null) {
                //consultamos la tabla programacion contratos con el contrato
                $queryProgramacionContrato = tbl_programacion_contrato::where('CONTRATO', $item->contrato)->first();
                // consultamos la tabla de recepcion vne detalle para saber si la orden tiene volantes
                $queryVneDetalle = TblRecepcionVneDetalle::where('ordenTrabajo', $item->orden)->first();

                if ($queryProgramacionContrato == null && $queryVneDetalle == null) {
                    $estadoGestion = "En campo";
                }

                // En campo con volantes previos
                if ($queryVneDetalle != null) {
                    $estadoGestion = "En campo con volantes previos";
                }

                // En campo con programacion incumplida
                if ($queryProgramacionContrato != null && $queryVneDetalle == null && $item->estado_programacion == null) {
                    $estadoGestion = "En campo con programacion incumplida";
                }

                //En campo con programacion a futuro
                if ($queryProgramacionContrato != null && $queryProgramacionContrato->FECHA_AGENDAMIENTO > $fechaActual->format('Y-m-d')) {
                    $estadoGestion = "En campo con programacion a futuro";
                }
            } else {
                // validamos si es masiva o externa
                if ($item->orden_solicitud_externa != null || $item->tipo_trabajo == "12161") {
                    $estadoGestion = "Externa sin asignar";
                } else {
                    $estadoGestion = "Masiva sin asignar";
                }
            }
            //--------------

            // Ejecutada efectiva por legalizar
            if ($item->estado_programacion != null && $item->status == 1) {
                $estadoGestion == "Ejecutada efectiva por legalizar";
            }
            //--------------------------------

            //Pendiente anulacion
            if ($item->causa_cierre != null) {
                $estadoGestion = "Pendiente anulacion";
            }

            //Validar suspendido por cartera
            if (strpos($item->estado_corte, "3") !== false || strpos($item->estado_corte, "5") !== false) {
                $estadoGestion = "Validar suspendido por cartera";
            }

            return [
                'indice' => $index + 1 + $offset,
                'orden' => $item->orden,
                'contrato' => $item->contrato,
                'producto' => $item->producto,
                // 1. ASIGNACION BASE OSF
                'numero_solicitud' => $item->numero_solicitud,
                'tipo_solicitud' => $item->tipo_solicitud,
                'NIT_CC' => $item->NIT_CC,
                'nombre_lugar' => $item->nombre_lugar,
                'departamento' => $item->departamento,
                'localidad' => $item->localidad,
                'sector_operativo' => $item->sector_operativo,
                'direccion' => $item->direccion,
                'consecutivo_ruta' => $item->consecutivo_ruta,
                'telefono' => $item->telefono,
                'medidor' => $item->medidor,
                'categoria' => $item->categoria,
                'unidad_operativa' => $item->unidad_operativa,
                'tipo_trabajo' => $item->tipo_trabajo,
                'fecha_asignacion' => $item->fecha_asignacion,
                'observacion_solicitud' => $item->observacion_solicitud,
                // 2. INFORMACIÓN COMPLEMENTARIA 12161
                'orden_solicitud_externa' => $item->orden_solicitud_externa,
                'tipo_solicitud_externa' => $item->tipo_solicitud_externa,
                'fecha_solicitud_externa' => $item->fecha_solicitud_externa,
                'observacion_externa' => $item->observacion_externa,
                'fecha_reasignacion_externa' => $item->fecha_reasignacion_externa,
                // 3. PROGRAMACIÓN DE ORDENES
                'FECHA_AGENDAMIENTO' => $fechaAgendamiento,
                'jornada' => $jornada,
                'CELULAR' => $celular,
                'OBSERVACIONES' => $observaciones,
                'estado_programacion' => $item->estado_programacion,
                // 4. ASIGNACIÓN INSPECTOR
                'codigo_tecnico' => $item->codigo_tecnico,
                'fecha_asignacion_inspector' => $item->fecha_asignacion_inspector,
                // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
                'estado_recepcion' => $estadoRecepcion,
                'fecha_recepcion' => $fechaRecepcion,
                'cantidad_vne' => $totalVneOrden,
                'ultima_vne' => $ultimaVne,
                'fecha_ultima_vne' => $fechaUltimaVne,
                'inspector_ultima_vne' => $inspectorUltimaVne,
                'compilado_observacion' => $compiladoObservacion,
                'causa_cierre' => $item->causa_cierre,
                'fecha_solicitud_cierre' => $item->fecha_solicitud_cierre,
                // 6.GESTIÓN REALIZADA OFICINA
                'num_acta' => $numActa,
                'validacion_formato' => $validacionFormato,
                'observacion_rechazo' => $observacionRechazo,
                // 7. FORMULACIÓN Y CALCULO
                'dia_ingreso' => $diaIngreso,
                'tipo_orden' => $tipoOrden,
                'fecha_legalizacion' => $item->fecha_legalizacion,
                'des_causal' => $item->des_causal,
                'observacion_legalizacion' => $item->comentario_legalizacion,
                'cod_causal' => $item->cod_causal,
                'dias_proceso' => $item->dias_proceso,

                'sede' => $querySede->nombre,
                'grupo' => $queryGrupos->grupo,
                'subgrupo' => $querySubGrupos->subgrupo,
                'meses' => $mesesTranscurridos,
                'fecha_vence_certificado' => $fechaVence,
                'dias_ejecutar' => $item->dias_ejecutar,
                'cumplimiento_politicas' => $cumplePoliticas,
                'cartera' => $cartera,
                'consumo' => $item->estado_producto,
                'fecha_ult_cert' => $fechaUltCert,
                'estado_gestion' => "Terminada efectiva",
                'ult_comentario' => $item->ult_comentario,
                'nom_inspector' => $item->nom_inspector,
                'dias_gestion_actual' => 0,
                'fecha_actual' => explode(" ", $item->updated_at)[0],
            ];
        });

        return response()->json(
            [
                'data' => $datosConIndice,
                'totalResults' => $totalResults
            ]
        );
    }

    function calcularDiasRestantes($fechaCreacion, $diasParaEjecutar)
    {
        // Convertir la fecha de creación en un objeto DateTime
        $fechaCreacion = new DateTime($fechaCreacion);
        $fechaActual = new DateTime(); // Fecha actual

        // Calcular la diferencia en días entre la fecha actual y la fecha de creación
        $diferencia = $fechaActual->diff($fechaCreacion);

        // Obtener la diferencia en días
        $diasTranscurridos = $diferencia->days;

        // Calcular los días restantes
        $diasRestantes = $diasParaEjecutar - $diasTranscurridos;

        return $diasRestantes;
    }

    function calcularDiasRestantesMenor60meses($fechaActual, $fechaLimite)
    {
        // Convertir las fechas en objetos DateTime
        $fechaActual = new DateTime();
        $fechaLimite = new DateTime($fechaLimite);

        // Calcular la diferencia en días entre la fecha límite y la fecha actual
        $diferencia = $fechaLimite->diff($fechaActual);

        // Obtener la diferencia en días
        $diasRestantes = $diferencia->days;

        // Si la fecha actual es mayor que la fecha límite, devolvemos días negativos
        if ($fechaActual > $fechaLimite) {
            $diasRestantes = -$diasRestantes;
        }
        return $diasRestantes;
    }

    public function marcaOrden(Request $request){

        $orden = $request->input('ordenEnviar');

        // consultamos la orden en asignadas
        $queryAsignadas = asignadas::where('orden', $orden)->first();
        if($queryAsignadas->marca == 0){
            $queryAsignadas->marca = 1;
        }else{
            $queryAsignadas->marca = 0;
        }
        $resultado = $queryAsignadas->save();

        if($resultado){
            echo 1;
        }else{
            echo 2;
        }
    }

    public function marcaOrdenMasiva(Request $request){

        set_time_limit(400);

        if (isset($request->all()['datosFormulario'])) {
            $data = $request->all()['datosFormulario'];
        } else {
            $data = [];
        }

        $marca = $request->input('marca');

        $query = asignadas::select('*')
            ->whereIn('asignadas.tipo_trabajo', [10444, 12161])
            ->where('status', 1);

        if (!empty($data)) {
            foreach ($data as $key => $value) {

                $flag = false;

                if ($key == 'contrato') {
                    $key = 'asignadas' . '.' . $key;
                }

                if ($key == 'id_sede') {
                    $query->leftJoin('tbl_localidades_municipios', 'tbl_localidades_municipios.nombre', '=', 'asignadas.localidad');
                    $key = 'tbl_localidades_municipios' . '.' . $key;
                }

                $arrayValues = [];
                $operator = 'IN';

                if ($key != 'dias') {
                    if (!is_array($value) && strpos($value, ',') !== false) {
                        $valueSeparate = explode(",", $value);
                        foreach ($valueSeparate as $value) {
                            $arrayValues[] = intval($value);
                        }
                    } else {
                        if (!is_array($value)) {
                            $arrayValues[] = intval($value);
                        } else if ($key != "localidad" && $key != "sector_operativo") {
                            foreach ($value as $val) {
                                $arrayValues[] = intval($val);
                            }
                        }
                    }
                }

                if ($key == 'datos') {

                    $arrayIdMun = [];
                    $arrayNomMun = [];
                    $queryDetails = TblGruposDetalle::select('id_mun');

                    $arrayIdGroup = [];
                    $arrayIdSubGroup = [];
                    $arrayIdSede = [];

                    if(isset($data['id_sede'])){
                        foreach($data['id_sede'] as $valueId){
                            $arrayIdSede[] = intval($valueId);
                        }
                    }


                    if (isset($value['id_grupo'])) {
                        foreach ($value['id_grupo'] as $valGroup) {
                            $arrayIdGroup[] = intval($valGroup);
                        }
                        $queryDetails->wherein('id_grupo', $arrayIdGroup);
                    }

                    if (isset($value['id_subGrupo'])) {
                        foreach ($value['id_subGrupo'] as $valSubGroup) {
                            $arrayIdSubGroup[] = intval($valSubGroup);
                        }
                        $queryDetails->wherein('id_subgrupo', $arrayIdSubGroup);
                    }
                    $dataDetail = $queryDetails->groupBy('id_mun')->get();

                    foreach ($dataDetail as $detail) {
                        $arrayIdMun[] = $detail->id_mun;
                    }

                    foreach ($arrayIdMun as $idMun) {
                        // consultamos el nombre del municipio con el id del municipio
                        $sqlMunQuery = tbl_localidades_municipio::where('id', $idMun)
                                                                ->whereIn('id_sede', $arrayIdSede);

                        // Ejecutar la consulta y obtener los resultados
                        $sqlMun = $sqlMunQuery->get();
                        $arrayNomMun[] = $sqlMun[0]->nombre;
                    }

                    $arrayValues = $arrayNomMun;

                    $key = "localidad";
                    $flag = true;
                }

                if (($key == 'localidad' || $key == 'sector_operativo') && !$flag) {
                    $operator = 'LIKE';
                    $values = ['%' . $value . '%'];
                }

                if ($key != 'dias') {
                    if ($operator === 'IN') {
                        $query->whereIn($key, $arrayValues);
                    } else {
                        $query->where($key, $operator, $values[0]);
                    }
                }

                if ($key == 'dias') {
                    $key = 'dias_ejecutar';

                    if (isset($value['dia_inicio']) && $value['dia_inicio'] != null) {
                        $dia_inicio = intval($value['dia_inicio']);
                    } else {
                        $dia_inicio = "";
                    }

                    if (isset($value['dia_fin']) && $value['dia_fin'] != null) {
                        $dia_fin = intval($value['dia_fin']);
                    } else {
                        $dia_fin = "";
                    }

                    if ($dia_inicio != "" && $dia_fin == "") {
                        // Condición para cuando dia_inicio es positivo y dia_fin llega vacío o no existe
                        $query->where($key, '<=', $dia_inicio);
                    } else if ($dia_inicio != ""  && $dia_fin != "") {
                        // Si los dos dias son positivos, debe traer los registros entre esos dias
                        $minValue = min($dia_inicio, $dia_fin);
                        $maxValue = max($dia_inicio, $dia_fin);
                        $query->whereBetween($key, [$minValue, $maxValue]);
                    }
                }
            }
        }

        $datos = $query->get();

        foreach($datos as $val){
            // actualizamos el valor de todas los registros que traiga datos
            $updateDatos = asignadas::where('id', $val->id)->first();

            if($marca == "true"){
                $updateDatos->marca = 1;
            }else{
                $updateDatos->marca = 0;
            }
            $updateDatos->save();
        }

        if($marca == "true"){
            echo 1;
        }else{
            echo 2;
        }
    }

    public function planilla(){
        $inspectors = tbl_insp_cali::all();
        return view('gestion.planilla', compact('inspectors'));
    }

    public function generarExcelPdf(Request $request){

        require '../vendor/autoload.php';

        set_time_limit(400);
        ini_set('memory_limit', '1024M');

        $inspector = intval($request->input('inspectorPlanilla'));
        $parametro = $request->input('parametro');
        $tipoOrden = $request->input('tipoOrden');
        $fechaAsignacion = $request->input('fechaAsignacion');
        $expExcel = $request->input('expExcel');
        $expPdf = $request->input('expPdf');

        if($inspector == 0){
            return redirect()->route('planilla')->with('error', 'Por favor seleccione un inspector');
        }

        if($expExcel == null && $expPdf == null){
            return redirect()->route('planilla')->with('error', 'Por favor seleccione un metodo de exporte');
        }

        // para generar el pdf
        $mpdf = new Mpdf();

        // para generar el excel
        $spread = new Spreadsheet();

        // Agregar datos al archivo Excel
        $sheet = $spread->getActiveSheet();
        $sheet->setTitle("Coordinacion RP");

        $sheet->mergeCells('A1:O1');

        // consultamos el nombre del inspector con el id que enviamos
        $queryInspector = tbl_insp_cali::where('id', $inspector)->first();

        if($expExcel == "on"){
            $arrayHeader = [
                "A1" => "Asignación ".$queryInspector->apellidos." ".$queryInspector->nombres,
                "A2" => "Item",
                "B2" => "Orden",
                "C2" => "Contrato",
                "D2" => "Localidad",
                "E2" => "Sub zona",
                "F2" => "Barrio",
                "G2" => "Direccion - Usuario",
                "H2" => "ET",
                "I2" => "Categoria - Medidor - Ultima revision",
                "J2" => "TT",
                "K2" => "Mes",
                "L2" => "Fecha en que vence",
                "M2" => "Fecha asig./ progra.",
                "N2" => "Jornada",
                "O2" => "Observaciones solicitud o programación",
            ];

            // Crear la primera imagen
            $drawing1 = new Drawing();
            $drawing1->setName('Logo');
            $drawing1->setDescription('Logo de la asignación');
            $drawing1->setPath('img/logo-ec-isotipo.png'); // Ruta de la primera imagen
            $drawing1->setCoordinates('A1'); // Posiciona la imagen en A1
            $drawing1->setOffsetX(10); // Ajusta el desplazamiento horizontal
            $drawing1->setOffsetY(10); // Ajusta el desplazamiento vertical
            $drawing1->setWidth(120); // Ancho de la primera imagen
            $drawing1->setHeight(50); // Altura de la primera imagen
            $drawing1->setWorksheet($sheet);

            // Crear la segunda imagen
            $drawing2 = new Drawing();
            $drawing2->setName('Logo adicional');
            $drawing2->setDescription('Logo adicional logo gdo');
            $drawing2->setPath('img/gdo.png'); // Ruta de la segunda imagen
            $drawing2->setCoordinates('O1'); // Mismo A1
            $drawing2->setOffsetX(10); // Desplazamiento horizontal mayor para colocarla al final
            $drawing2->setOffsetY(10); // Mantén el desplazamiento vertical similar al primero
            $drawing2->setWidth(120); // Ancho de la segunda imagen
            $drawing2->setHeight(50); // Altura de la segunda imagen
            $drawing2->setWorksheet($sheet);

            // Ajustar la altura de la fila
            $sheet->getRowDimension(1)->setRowHeight(50);

            // Alinear contenido de A1 si es necesario
            $sheet->getStyle('A1')->getAlignment()->applyFromArray([
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]);

            $sheet->getStyle('O1')->getAlignment()->applyFromArray([
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]);

            foreach ($arrayHeader as $key => $header) {
                // asignamos el rango de columnas a lque queremos aplicar el color asigado en $color
                if ($key == "A1") {
                    $color = 'FFD9E6F5';#D9E6F5
                    $rango = 'A1:O1';
                    $size = 20;
                    $colorFont = 'FF000038';
                }else{
                    $color = 'FF8DB4E2';
                    $rango = 'A2:O2';
                    $size = 12;
                    $colorFont = 'FFFFFFFF';
                }

                // asigamos el estilo al rango de celdas
                $sheet->getStyle($rango)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => $colorFont],
                        'size' => $size,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => $color],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // asognamos los demas encabezados
                $sheet->setCellValue($key, $header);

                // sacamos solo la letra de la columna para dimensionar la columna
                if (strpos($key, "2")) {
                    $key = explode("2", $key)[0];
                }else {
                    $key = explode("1", $key)[0];
                }

                if ($key === 'G' || $key === 'I' || $key === 'O') {
                    $sheet->getColumnDimension($key)->setWidth(30); // Ancho personalizado
                }else{
                    $sheet->getColumnDimension($key)->setAutoSize(true);
                }
            }
        }

        if ($parametro === null) {
            // Caso 1: Sin parámetro
            $queryAsignadas = asignadas::where('codigo_tecnico', $inspector)
                ->where('status', 1)
                ->get();
        } elseif ($parametro === "2") {
            // Caso 2: Con marca
            $query = asignadas::where('marca', 1)->where('status', 1);

            if ($tipoOrden === "1") {
                $query->where('tipo_trabajo', 10444);
            } else {
                $query->whereIn('tipo_solicitud_externa', [12161, 12163, 12164]);
            }

            $queryAsignadas = $query->get();
        } elseif ($parametro === "1") {
            // Caso 3: Con fecha de asignación
            if ($fechaAsignacion === null) {
                return redirect()->route('planilla')->with('error', 'Por favor seleccione una fecha');
            }

            $queryAsignadas = asignadas::where('codigo_tecnico', $inspector)
                ->where('fecha_asignacion_inspector', $fechaAsignacion)
                ->where('status', 1)
                ->get();
        }


        if($queryAsignadas->isEmpty()){
            return redirect()->route('planilla')->with('error', 'No hay resultados en la busqueda');
        }

        $item = 0;
        $arrayExcel = [];
        foreach($queryAsignadas as $asignada){
            if($asignada->estado_programacion == "Programada"){
                if($parametro == "2"){
                    if($asignada->orden_solicitud_externa != null){
                        $orden = $asignada->orden_solicitud_externa;
                        $estadoTrabajo = $asignada->estado_producto;
                        $tipoTrabajo = $asignada->tipo_solicitud_externa;
                    }else{
                        $orden = $asignada->orden;
                        $estadoTrabajo = "INTER.";
                        $tipoTrabajo = $asignada->tipo_trabajo;
                    }
                }else if($parametro == "1" || $parametro == null){
                    if($tipoOrden == "1"){
                        if($asignada->orden_solicitud_externa != null){
                            continue;
                        }
                        $orden = $asignada->orden;
                        $estadoTrabajo = "INTER.";
                        $tipoTrabajo = $asignada->tipo_trabajo;
                    }else if($tipoOrden == "2"){
                        if($asignada->orden_solicitud_externa == null){
                            continue;
                        }
                        $orden = $asignada->orden_solicitud_externa;
                        $estadoTrabajo = $asignada->estado_producto;
                        $tipoTrabajo = $asignada->tipo_solicitud_externa;
                    }else if($tipoOrden == null){
                        if($asignada->orden_solicitud_externa != null){
                            $orden = $asignada->orden_solicitud_externa;
                            $estadoTrabajo = $asignada->estado_producto;
                            $tipoTrabajo = $asignada->tipo_solicitud_externa;
                        }else{
                            $orden = $asignada->orden;
                            $estadoTrabajo = "INTER.";
                            $tipoTrabajo = $asignada->tipo_trabajo;
                        }
                    }
                }

                $item ++;

                $contrato = $asignada->contrato;

                $queryMunicipio = tbl_localidades_municipio::where('nombre', $asignada->localidad)->first();

                // Consultamos primero el municipio
                $queryLugar = $queryMunicipio;
                $columnaLugar = 'id_mun';

                // Si no se encuentra el detalle por municipio, buscamos por barrio
                $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();

                if ($queryDetalle == null) {
                    // Ahora buscamos por barrio si no encontramos el detalle por municipio
                    $queryLugar = TblBarrios::where('barrio', $asignada->sector_operativo)->first();
                    $columnaLugar = 'id_barrio';

                    if ($queryLugar != null) {
                        $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();
                    }
                }

                // Consultamos la tabla de subgrupos
                $querySubGrupos = TblSubgrupo::where('id', $queryDetalle->id_subGrupo)->first();

                $direccionUsuario = "1)Dir: ".$asignada->direccion."//"."2)Usu: ".$asignada->nombre_lugar;

                $fechaUltCert = strtotime($asignada->fecha_ult_cert);
                $fechaUltCert = gmdate('d-M-y', $fechaUltCert);
                $fechaUltCert = strtolower($fechaUltCert);

                $catMedUltRev = "1)Categoría: ".$asignada->categoria."//"."2)Medidor: ".$asignada->medidor."//"."3)Ultima rev: ".$fechaUltCert;

                // CALCULO DE LOS MESES
                if ($asignada->fecha_ult_cert != '1970-01-01') {
                    $fechaCertificacion = new DateTime($asignada->fecha_ult_cert); // Fecha de certificación
                    $fechaActual = new DateTime(); // Fecha actual
                    // Obtener la diferencia entre las dos fechas
                    $diferencia = $fechaCertificacion->diff($fechaActual);
                    // Calcular los meses transcurridos
                    $mesesTranscurridos = ($diferencia->y * 12) + $diferencia->m;
                    // Verificar si los días adicionales suman un mes más
                    if ($diferencia->d > 0) {
                        $mesesTranscurridos++;
                    }
                } else {
                    $mesesTranscurridos = 60;
                }

                if($mesesTranscurridos >= 59){
                    $fechaVence = "V";
                }else{
                    if($asignada->vence != null){
                        $fechaVence = strtotime($asignada->vence);
                        $fechaVence = gmdate('d-M-y', $fechaVence);
                        $fechaVence = strtolower($fechaVence);
                    }else{
                        $fechaVence = "";
                    }
                }

                if($asignada->fecha_asignacion_inspector != null){
                    $fechaAsignacionPro = strtotime($asignada->fecha_asignacion_inspector);
                    $fechaAsignacionPro = gmdate('d-M-y',$fechaAsignacionPro);
                    $fechaAsignacionPro = strtolower($fechaAsignacionPro);
                }else{
                    $fechaAsignacionPro = "";
                }

                // consultamos la tabla de contratos con el numero de contraro
                $queryProgramacionContrato = tbl_programacion_contrato::where('CONTRATO', $asignada->contrato)->first();

                if ($queryProgramacionContrato != null) {
                    $jornada = explode(" ", $queryProgramacionContrato->HORA_INICIO);
                    $jornada = $jornada[1];
                    $observaciones = $queryProgramacionContrato->OBSERVACIONES;
                } else {
                    $jornada = "";
                    if($asignada->observacion_externa != null){
                        $observaciones = $asignada->observacion_externa;
                    }else{
                        // consultamos la tabla de vne con el numero de orden
                        $queryDetalleVne = TblRecepcionVneDetalle::where('ordenTrabajo', $asignada->orden)
                                                                    ->orderBy('id', 'desc')->limit(1)
                                                                    ->get();

                        if($queryDetalleVne->isNotEmpty()){
                            $observaciones = $queryDetalleVne[0]->comObservacion;
                        }else{
                            $observaciones = "";
                        }
                    }
                }

                $arrayExcel[] = [
                    'A' => $item,
                    'B' => $orden,
                    'C' => $contrato,
                    'D' => $asignada->localidad,
                    'E' => $querySubGrupos->subgrupo,
                    'F' => $asignada->sector_operativo,
                    'G' => $direccionUsuario,
                    'H' => $estadoTrabajo,
                    'I' => $catMedUltRev,
                    'J' => $tipoTrabajo,
                    'K' => $mesesTranscurridos,
                    'L' => $fechaVence,
                    'M' => $fechaAsignacionPro,
                    'N' => $jornada,
                    'O' => $observaciones
                ];
            }
        }

        if(empty($arrayExcel)){
            return redirect()->route('planilla')->with('error', 'No hay resultados en la busqueda');
        }

        if($expExcel == "on" && $expPdf == null){
            $fila = 3;
            foreach ($arrayExcel as $values) {
                foreach ($values as $key => $val) {
                    // Lógica para procesar los valores y aplicar el formato
                    if ($key == "G" || $key == "I") {
                        $valPartes = explode("//", $val);
                        $textoFinal = implode("\n", $valPartes);
                        $sheet->setCellValue($key . $fila, $textoFinal);
                        $sheet->getStyle($key . $fila)->getAlignment()->setWrapText(true);
                    } elseif ($key == "O") {
                        $sheet->setCellValue($key . $fila, $val);
                        $sheet->getStyle($key . $fila)->getAlignment()->setWrapText(true);
                    } elseif ($key == "K") {
                        $sheet->setCellValue($key . $fila, $val);
                        if ($val >= 59) {
                            $sheet->getStyle($key . $fila)->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FFC7CE'],
                                ],
                            ]);
                        }
                    } else {
                        $sheet->setCellValue($key . $fila, $val);
                    }
                    $sheet->getStyle($key . $fila)->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                        'font' => [
                            'size' => 8,
                        ],
                    ]);
                }
                $fila++;
            }

            unset($arrayExcel);

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spread, 'Xlsx');
            $tempFile = tempnam(sys_get_temp_dir(), 'reporte');
            $writer->save($tempFile);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Planilla_asignacion.xlsx"');
            header('Cache-Control: max-age=0');
            readfile($tempFile);
            unlink($tempFile);

            exit;

        }else if($expPdf == "on" && $expExcel == null){
            // Generamos el PDF con el formato HTML
            $html = "
                <style>
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        font-family: Arial, sans-serif;
                        font-size: 8px;
                        table-layout: auto;
                    }
                    th, td {
                        border: 1px solid black;
                        padding: 4px;
                        text-align: left;
                        vertical-align: top;
                    }
                    .header {
                        font-weight: bold;
                        background-color: #f2f2f2;
                    }
                    .observaciones {
                        height: 60px;
                        text-align: left; /* Alinea el texto a la izquierda */
                        vertical-align: top; /* Opcional: alinea el texto hacia la parte superior de la celda */
                        padding-left: 4px; /* Opcional: agrega un pequeño margen interno para mayor claridad */
                    }
                    .title {
                        font-weight: bold;
                        background-color: lightgrey;
                    }
                    .orden-col {
                        width: 7%; /* Hace que esta celda sea mucho más angosta */
                    }
                    .barrio-col{
                        width: 7%;
                    }
                    .usuario-col{
                        width: 10%;
                    }
                </style>";

            foreach($arrayExcel as $values){

                // direccion y usuario
                $partesDireccionUsuario = explode("//", $values['G']);
                $partesDireccion = explode("1)Dir: ", $partesDireccionUsuario[0]);
                $direccion = $partesDireccion[1];

                $partesUsuario = explode("2)Usu: ", $partesDireccionUsuario[1]);
                $usuario = $partesUsuario[1];

                // categoria
                $partesCatMedUlt = explode("//", $values['I']);
                $categoria = explode(" ", $partesCatMedUlt[0]);

                $medidor = explode(" ", $partesCatMedUlt[1]);

                $ultRevision = explode(" ", $partesCatMedUlt[2]);

                $html.= "
                    <table>
                        <tr>
                            <td class='title'>Orden:</td>
                            <td>".$values['B']."</td>
                            <td class='title'>Contrato:</td>
                            <td>".$values['C']."</td>
                            <td class='title'>Inspector:</td>
                            <td>".$queryInspector->apellidos." ".$queryInspector->nombres."</td>
                            <td class='title'>Localidad:</td>
                            <td>".$values['D']."</td>
                            <td class='title'>Sub zona:</td>
                            <td>".$values['E']."</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class='title'>Barrio:</td>
                            <td>".$values['F']."</td>
                            <td class='title'>Dirección:</td>
                            <td colspan='3'>".$direccion."</td>
                            <td class='title'>Estado técnico</td>
                            <td colspan='3'>".$values['H']."</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class='title'>Usuario:</td>
                            <td>".$usuario."</td>
                            <td class='title'>Tipo categoría:</td>
                            <td>".$categoria[1]."</td>
                            <td class='title'># Medidor:</td>
                            <td>".$medidor[1]."</td>
                            <td class='title'>Fecha última revisión:</td>
                            <td colspan='3'>".$ultRevision[2]."</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class='title'>Tipo de trabajo:</td>
                            <td>".$values['J']."</td>
                            <td class='title'>Meses:</td>
                            <td>".$values['K']."</td>
                            <td class='title'>Fecha vence OT:</td>
                            <td>".$values['L']."</td>
                            <td class='title'>Fecha asignación o programación:</td>
                            <td>".$values['M']."</td>
                            <td class='title'>Jornada programación:</td>
                            <td>".$values['N']."</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class='title observaciones'>Observaciones solicitud o programación:</td>
                            <td colspan='9' class='observaciones'>
                                ".$values['O']."
                            </td>
                        </tr>
                    </table>
                    <hr>";
            }

            // Generar el PDF con mPDF
            $mpdf->WriteHTML($html);
            $mpdf->Output('reporte.pdf', 'D'); // Descargar el PDF

            exit;
        }else if($expExcel == "on" && $expPdf == "on"){
            // Generar el archivo Excel y pdf
            $html = "
                <style>
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        font-family: Arial, sans-serif;
                        font-size: 8px;
                        table-layout: auto;
                    }
                    th, td {
                        border: 1px solid black;
                        padding: 4px;
                        text-align: left;
                        vertical-align: top;
                    }
                    .header {
                        font-weight: bold;
                        background-color: #f2f2f2;
                    }
                    .observaciones {
                        height: 60px;
                        text-align: left; /* Alinea el texto a la izquierda */
                        vertical-align: top; /* Opcional: alinea el texto hacia la parte superior de la celda */
                        padding-left: 4px; /* Opcional: agrega un pequeño margen interno para mayor claridad */
                    }
                    .title {
                        font-weight: bold;
                        background-color: lightgrey;
                    }
                    .orden-col {
                        width: 7%; /* Hace que esta celda sea mucho más angosta */
                    }
                    .barrio-col{
                        width: 7%;
                    }
                    .usuario-col{
                        width: 10%;
                    }
                </style>";

            $fila = 3;
            foreach ($arrayExcel as $values) {
                foreach ($values as $key => $val) {
                    // Lógica para procesar los valores y aplicar el formato
                    if ($key == "G" || $key == "I") {
                        $valPartes = explode("//", $val);
                        $textoFinal = implode("\n", $valPartes);
                        $sheet->setCellValue($key . $fila, $textoFinal);
                        $sheet->getStyle($key . $fila)->getAlignment()->setWrapText(true);
                    } elseif ($key == "O") {
                        $sheet->setCellValue($key . $fila, $val);
                        $sheet->getStyle($key . $fila)->getAlignment()->setWrapText(true);
                    } elseif ($key == "K") {
                        $sheet->setCellValue($key . $fila, $val);
                        if ($val >= 59) {
                            $sheet->getStyle($key . $fila)->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FFC7CE'],
                                ],
                            ]);
                        }
                    } else {
                        $sheet->setCellValue($key . $fila, $val);
                    }
                    $sheet->getStyle($key . $fila)->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                        'font' => [
                            'size' => 8,
                        ],
                    ]);
                }

                $fila++;

                // direccion y usuario
                $partesDireccionUsuario = explode("//", $values['G']);
                $partesDireccion = explode("1)Dir: ", $partesDireccionUsuario[0]);
                $direccion = $partesDireccion[1];

                $partesUsuario = explode("2)Usu: ", $partesDireccionUsuario[1]);
                $usuario = $partesUsuario[1];

                // categoria
                $partesCatMedUlt = explode("//", $values['I']);
                $categoria = explode(" ", $partesCatMedUlt[0]);

                $medidor = explode(" ", $partesCatMedUlt[1]);

                $ultRevision = explode(" ", $partesCatMedUlt[2]);

                $html.= "
                    <table>
                        <tr>
                            <td class='title'>Orden:</td>
                            <td>".$values['B']."</td>
                            <td class='title'>Contrato:</td>
                            <td>".$values['C']."</td>
                            <td class='title'>Inspector:</td>
                            <td>".$queryInspector->apellidos." ".$queryInspector->nombres."</td>
                            <td class='title'>Localidad:</td>
                            <td>".$values['D']."</td>
                            <td class='title'>Sub zona:</td>
                            <td>".$values['E']."</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class='title'>Barrio:</td>
                            <td>".$values['F']."</td>
                            <td class='title'>Dirección:</td>
                            <td colspan='3'>".$direccion."</td>
                            <td class='title'>Estado técnico</td>
                            <td colspan='3'>".$values['H']."</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class='title'>Usuario:</td>
                            <td>".$usuario."</td>
                            <td class='title'>Tipo categoría:</td>
                            <td>".$categoria[1]."</td>
                            <td class='title'># Medidor:</td>
                            <td>".$medidor[1]."</td>
                            <td class='title'>Fecha última revisión:</td>
                            <td colspan='3'>".$ultRevision[2]."</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class='title'>Tipo de trabajo:</td>
                            <td>".$values['J']."</td>
                            <td class='title'>Meses:</td>
                            <td>".$values['K']."</td>
                            <td class='title'>Fecha vence OT:</td>
                            <td>".$values['L']."</td>
                            <td class='title'>Fecha asignación o programación:</td>
                            <td>".$values['M']."</td>
                            <td class='title'>Jornada programación:</td>
                            <td>".$values['N']."</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class='title observaciones'>Observaciones solicitud o programación:</td>
                            <td colspan='9' class='observaciones'>
                                ".$values['O']."
                            </td>
                        </tr>
                    </table>
                    <hr>";
            }

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spread, 'Xlsx');
            $excelTempFile = tempnam(sys_get_temp_dir(), 'reporte_excel');
            $writer->save($excelTempFile);

            $mpdf->WriteHTML($html);
            $pdfTempFile = tempnam(sys_get_temp_dir(), 'reporte_pdf');
            $mpdf->Output($pdfTempFile, 'F'); // Guardar el PDF temporalmente

            // Crear archivo ZIP
            $zip = new ZipArchive();
            $zipFile = tempnam(sys_get_temp_dir(), 'reporte_zip');
            if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($excelTempFile, 'Planilla_asignacion.xlsx');
                $zip->addFile($pdfTempFile, 'archivo.pdf');
                $zip->close();
            }

            // Enviar el archivo ZIP al usuario
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="reportes.zip"');
            header('Content-Length: ' . filesize($zipFile));
            readfile($zipFile);

            // Eliminar archivos temporales
            unlink($excelTempFile);
            unlink($pdfTempFile);
            unlink($zipFile);

            exit;
        }
        return null;
    }

    public function generarImpMasiva(Request $request){

        require '../vendor/autoload.php';

        set_time_limit(400);
        ini_set('memory_limit', '1024M');

        $sede = intval($request->input('sedeImpMas'));
        $tipoOrden = $request->input('tipoOrden');
        $fechaAsigna = $request->input('fechaAsigna');
        $fechaImpMasiva = $request->input('fechaImpMasiva');
        $expExcel = $request->input('expExcel');
        $expPdf = $request->input('expPdf');

        // consultamos la tabla asignadas con la sede que llega
        $queryAsignadas = DB::table('asignadas')
        ->join('tbl_localidades_municipios', 'asignadas.localidad', '=', 'tbl_localidades_municipios.nombre')
        ->where('tbl_localidades_municipios.id_sede', $sede)
        ->where('asignadas.status', 1)
        ->whereNotNull('codigo_tecnico') // Validar que no sea NULL
        ->where('codigo_tecnico', '!=', ''); // Validar que no sea una cadena vacía

        if($fechaAsigna == "si"){
            $queryAsignadas->where('fecha_asignacion_inspector', $fechaImpMasiva);
        }

        $queryAsignadas->select('asignadas.*');

        $queryAsignadas = $queryAsignadas->get();

        if($queryAsignadas->isEmpty()){
            return redirect()->route('coordinacion')->with('error', 'No hay resultados en la busqueda');
        }

        if($expExcel == null && $expPdf == null){
            return redirect()->route('coordinacion')->with('error', 'Seleccione el metodo de exportación');
        }

        $spread = new Spreadsheet();

        // Agregar datos al archivo Excel
        $sheet = $spread->getActiveSheet();
        $sheet->setTitle("Coordinacion RP");

        $sheet->mergeCells('A1:O1');

        if($expExcel == "on"){
            $arrayHeader = [
                "A1" => "Asignación",
                "A2" => "Item",
                "B2" => "Orden",
                "C2" => "Contrato",
                "D2" => "Localidad",
                "E2" => "Sub zona",
                "F2" => "Barrio",
                "G2" => "Direccion - Usuario",
                "H2" => "ET",
                "I2" => "Categoria - Medidor - Ultima revision",
                "J2" => "TT",
                "K2" => "Mes",
                "L2" => "Fecha en que vence",
                "M2" => "Fecha asig./ progra.",
                "N2" => "Jornada",
                "O2" => "Observaciones solicitud o programación",
            ];

            // Crear la primera imagen
            $drawing1 = new Drawing();
            $drawing1->setName('Logo');
            $drawing1->setDescription('Logo de la asignación');
            $drawing1->setPath('img/logo-ec-isotipo.png'); // Ruta de la primera imagen
            $drawing1->setCoordinates('A1'); // Posiciona la imagen en A1
            $drawing1->setOffsetX(10); // Ajusta el desplazamiento horizontal
            $drawing1->setOffsetY(10); // Ajusta el desplazamiento vertical
            $drawing1->setWidth(120); // Ancho de la primera imagen
            $drawing1->setHeight(50); // Altura de la primera imagen
            $drawing1->setWorksheet($sheet);

            // Crear la segunda imagen
            $drawing2 = new Drawing();
            $drawing2->setName('Logo adicional');
            $drawing2->setDescription('Logo adicional logo gdo');
            $drawing2->setPath('img/gdo.png'); // Ruta de la segunda imagen
            $drawing2->setCoordinates('O1'); // Mismo A1
            $drawing2->setOffsetX(10); // Desplazamiento horizontal mayor para colocarla al final
            $drawing2->setOffsetY(10); // Mantén el desplazamiento vertical similar al primero
            $drawing2->setWidth(120); // Ancho de la segunda imagen
            $drawing2->setHeight(50); // Altura de la segunda imagen
            $drawing2->setWorksheet($sheet);

            // Ajustar la altura de la fila
            $sheet->getRowDimension(1)->setRowHeight(50);

            // Alinear contenido de A1 si es necesario
            $sheet->getStyle('A1')->getAlignment()->applyFromArray([
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]);

            $sheet->getStyle('O1')->getAlignment()->applyFromArray([
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]);

            foreach ($arrayHeader as $key => $header) {
                // asignamos el rango de columnas a lque queremos aplicar el color asigado en $color
                if ($key == "A1") {
                    $color = 'FFD9E6F5';#D9E6F5
                    $rango = 'A1:O1';
                    $size = 20;
                    $colorFont = 'FF000038';
                }else{
                    $color = 'FF8DB4E2';
                    $rango = 'A2:O2';
                    $size = 12;
                    $colorFont = 'FFFFFFFF';
                }

                // asigamos el estilo al rango de celdas
                $sheet->getStyle($rango)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => $colorFont],
                        'size' => $size,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => $color],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // asognamos los demas encabezados
                $sheet->setCellValue($key, $header);

                // sacamos solo la letra de la columna para dimensionar la columna
                if (strpos($key, "2")) {
                    $key = explode("2", $key)[0];
                }else {
                    $key = explode("1", $key)[0];
                }

                if ($key === 'G' || $key === 'I' || $key === 'O') {
                    $sheet->getColumnDimension($key)->setWidth(30); // Ancho personalizado
                }else{
                    $sheet->getColumnDimension($key)->setAutoSize(true);
                }
            }
        }

        $asignadasPorInspector = [];
        foreach($queryAsignadas as $asignadas){
            $inspector = $asignadas->codigo_tecnico;
            if (!isset($asignadasPorInspector[$inspector])) {
                $asignadasPorInspector[$inspector] = [];
            }
            $asignadasPorInspector[$inspector][] = $asignadas;
        }

        $arrayExcel = [];
        foreach($asignadasPorInspector as $key => $value){
            $item = 0;
            foreach($value as $val){
                if($tipoOrden == "1"){
                    if($val->orden_solicitud_externa != null){
                        continue;
                    }
                    $orden = $val->orden;
                    $estadoTrabajo = "INTER.";
                    $tipoTrabajo = $val->tipo_trabajo;
                }else if($tipoOrden == "2"){
                    if($val->orden_solicitud_externa == null){
                        continue;
                    }
                    $orden = $val->orden_solicitud_externa;
                    $estadoTrabajo = $val->estado_producto;
                    $tipoTrabajo = $val->tipo_solicitud_externa;
                }else if($tipoOrden == null){
                    if($val->orden_solicitud_externa != null){
                        $orden = $val->orden_solicitud_externa;
                        $estadoTrabajo = $val->estado_producto;
                        $tipoTrabajo = $val->tipo_solicitud_externa;
                    }else{
                        $orden = $val->orden;
                        $estadoTrabajo = "INTER.";
                        $tipoTrabajo = $val->tipo_trabajo;
                    }
                }

                $item ++;

                $contrato = $val->contrato;

                $queryMunicipio = tbl_localidades_municipio::where('nombre', $val->localidad)->first();

                $queryLugar = $queryMunicipio;
                $columnaLugar = 'id_mun';

                // Si no se encuentra el detalle por municipio, buscamos por barrio
                $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();

                if ($queryDetalle == null) {
                    // Ahora buscamos por barrio si no encontramos el detalle por municipio
                    $queryLugar = TblBarrios::where('barrio', $val->sector_operativo)->first();
                    $columnaLugar = 'id_barrio';

                    if ($queryLugar != null) {
                        $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();
                    }
                }

                $querySubGrupos = TblSubgrupo::where('id', $queryDetalle->id_subGrupo)->first();

                $direccionUsuario = "1)Dir: ".$val->direccion."//"."2)Usu: ".$val->nombre_lugar;

                $fechaUltCert = strtotime($val->fecha_ult_cert);
                $fechaUltCert = gmdate('d-M-y', $fechaUltCert);
                $fechaUltCert = strtolower($fechaUltCert);

                $catMedUltRev = "1)Categoría: ".$val->categoria."//"."2)Medidor: ".$val->medidor."//"."3)Ultima rev: ".$fechaUltCert;

                // CALCULO DE LOS MESES
                if ($val->fecha_ult_cert != '1970-01-01') {
                    $fechaCertificacion = new DateTime($val->fecha_ult_cert); // Fecha de certificación
                    $fechaActual = new DateTime(); // Fecha actual
                    // Obtener la diferencia entre las dos fechas
                    $diferencia = $fechaCertificacion->diff($fechaActual);
                    // Calcular los meses transcurridos
                    $mesesTranscurridos = ($diferencia->y * 12) + $diferencia->m;
                    // Verificar si los días adicionales suman un mes más
                    if ($diferencia->d > 0) {
                        $mesesTranscurridos++;
                    }
                } else {
                    $mesesTranscurridos = 60;
                }

                if($mesesTranscurridos >= 59){
                    $fechaVence = "V";
                }else{
                    if($val->vence != null){
                        $fechaVence = strtotime($val->vence);
                        $fechaVence = gmdate('d-M-y', $fechaVence);
                        $fechaVence = strtolower($fechaVence);
                    }else{
                        $fechaVence = "";
                    }
                }

                if($val->fecha_asignacion_inspector != null){
                    $fechaAsignacionPro = strtotime($val->fecha_asignacion_inspector);
                    $fechaAsignacionPro = gmdate('d-M-y',$fechaAsignacionPro);
                    $fechaAsignacionPro = strtolower($fechaAsignacionPro);
                }else{
                    $fechaAsignacionPro = "";
                }

                // consultamos la tabla de contratos con el numero de contraro
                $queryProgramacionContrato = tbl_programacion_contrato::where('CONTRATO', $val->contrato)->first();

                if ($queryProgramacionContrato != null) {
                    $jornada = explode(" ", $queryProgramacionContrato->HORA_INICIO);
                    $jornada = $jornada[1];
                    $observaciones = $queryProgramacionContrato->OBSERVACIONES;
                } else {
                    $jornada = "";
                    if($val->observacion_externa != null){
                        $observaciones = $val->observacion_externa;
                    }else{
                        // consultamos la tabla de vne con el numero de orden
                        $queryDetalleVne = TblRecepcionVneDetalle::where('ordenTrabajo', $val->orden)
                                                                    ->orderBy('id', 'desc')->limit(1)
                                                                    ->get();

                        if($queryDetalleVne->isNotEmpty()){
                            $observaciones = $queryDetalleVne[0]->comObservacion;
                        }else{
                            $observaciones = "";
                        }
                    }
                }

                $codigo_tecico = $val->codigo_tecnico;

                if(!isset($arrayExcel[$codigo_tecico])){
                    $arrayExcel[$codigo_tecico] = [];
                }

                $arrayExcel[$codigo_tecico][] = [
                    'A' => $item,
                    'B' => $orden,
                    'C' => $contrato,
                    'D' => $val->localidad,
                    'E' => $querySubGrupos->subgrupo,
                    'F' => $val->sector_operativo,
                    'G' => $direccionUsuario,
                    'H' => $estadoTrabajo,
                    'I' => $catMedUltRev,
                    'J' => $tipoTrabajo,
                    'K' => $mesesTranscurridos,
                    'L' => $fechaVence,
                    'M' => $fechaAsignacionPro,
                    'N' => $jornada,
                    'O' => $observaciones,
                    'P' => $codigo_tecico
                ];
            }
        }

        $fechaActual = date("d-m-Y");

        $partesFecha = explode("-",$fechaActual);

        $anioActual = substr($partesFecha[2], -2);

        $fechaCompleta = $partesFecha[0]."".$partesFecha[1]."".$anioActual;

        if ($expExcel == "on" && $expPdf == null) {
            $zip = new ZipArchive();
            $zipFile = tempnam(sys_get_temp_dir(), 'Planillas') . '.zip';

            if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                foreach ($arrayExcel as $index => $values) {

                    $spread = new Spreadsheet();

                    $sheet = $spread->getActiveSheet();
                    $sheet->setTitle("Coordinacion RP");

                    $sheet->mergeCells('A1:O1');

                    $fila = 3; // Reiniciar fila para cada archivo de Excel

                    foreach ($values as $value) {

                        // consultamos los inspectores
                        $nombreTecnico = tbl_insp_cali::where('id', $value['P'])->first();

                        if($expExcel == "on"){

                            $arrayHeader = [
                                "A1" => "Asignación ".$nombreTecnico->apellidos." ".$nombreTecnico->nombres,
                                "A2" => "Item",
                                "B2" => "Orden",
                                "C2" => "Contrato",
                                "D2" => "Localidad",
                                "E2" => "Sub zona",
                                "F2" => "Barrio",
                                "G2" => "Direccion - Usuario",
                                "H2" => "ET",
                                "I2" => "Categoria - Medidor - Ultima revision",
                                "J2" => "TT",
                                "K2" => "Mes",
                                "L2" => "Fecha en que vence",
                                "M2" => "Fecha asig./ progra.",
                                "N2" => "Jornada",
                                "O2" => "Observaciones solicitud o programación",
                            ];

                            // Crear la primera imagen
                            $drawing1 = new Drawing();
                            $drawing1->setName('Logo');
                            $drawing1->setDescription('Logo de la asignación');
                            $drawing1->setPath('img/logo-ec-isotipo.png'); // Ruta de la primera imagen
                            $drawing1->setCoordinates('A1'); // Posiciona la imagen en A1
                            $drawing1->setOffsetX(10); // Ajusta el desplazamiento horizontal
                            $drawing1->setOffsetY(10); // Ajusta el desplazamiento vertical
                            $drawing1->setWidth(120); // Ancho de la primera imagen
                            $drawing1->setHeight(50); // Altura de la primera imagen
                            $drawing1->setWorksheet($sheet);

                            // Crear la segunda imagen
                            $drawing2 = new Drawing();
                            $drawing2->setName('Logo adicional');
                            $drawing2->setDescription('Logo adicional logo gdo');
                            $drawing2->setPath('img/gdo.png'); // Ruta de la segunda imagen
                            $drawing2->setCoordinates('O1'); // Mismo A1
                            $drawing2->setOffsetX(10); // Desplazamiento horizontal mayor para colocarla al final
                            $drawing2->setOffsetY(10); // Mantén el desplazamiento vertical similar al primero
                            $drawing2->setWidth(120); // Ancho de la segunda imagen
                            $drawing2->setHeight(50); // Altura de la segunda imagen
                            $drawing2->setWorksheet($sheet);

                            // Ajustar la altura de la fila
                            $sheet->getRowDimension(1)->setRowHeight(50);

                            // Alinear contenido de A1 si es necesario
                            $sheet->getStyle('A1')->getAlignment()->applyFromArray([
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ]);

                            $sheet->getStyle('O1')->getAlignment()->applyFromArray([
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ]);

                            foreach ($arrayHeader as $key => $header) {
                                // asignamos el rango de columnas a lque queremos aplicar el color asigado en $color
                                if ($key == "A1") {
                                    $color = 'FFD9E6F5';#D9E6F5
                                    $rango = 'A1:O1';
                                    $size = 20;
                                    $colorFont = 'FF000038';
                                }else{
                                    $color = 'FF8DB4E2';
                                    $rango = 'A2:O2';
                                    $size = 12;
                                    $colorFont = 'FFFFFFFF';
                                }

                                // asigamos el estilo al rango de celdas
                                $sheet->getStyle($rango)->applyFromArray([
                                    'font' => [
                                        'bold' => true,
                                        'color' => ['argb' => $colorFont],
                                        'size' => $size,
                                    ],
                                    'fill' => [
                                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                        'startColor' => ['argb' => $color],
                                    ],
                                    'alignment' => [
                                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                    ],
                                ]);

                                // asognamos los demas encabezados
                                $sheet->setCellValue($key, $header);

                                // sacamos solo la letra de la columna para dimensionar la columna
                                if (strpos($key, "2")) {
                                    $key = explode("2", $key)[0];
                                }else {
                                    $key = explode("1", $key)[0];
                                }

                                if ($key === 'G' || $key === 'I' || $key === 'O') {
                                    $sheet->getColumnDimension($key)->setWidth(30); // Ancho personalizado
                                }else{
                                    $sheet->getColumnDimension($key)->setAutoSize(true);
                                }
                            }
                        }

                        foreach ($value as $key => $val) {
                            if($key == 'P') continue;
                            if ($key == "G" || $key == "I") {
                                $valPartes = explode("//", $val);
                                $textoFinal = implode("\n", $valPartes);
                                $sheet->setCellValue($key . $fila, $textoFinal);
                                $sheet->getStyle($key . $fila)->getAlignment()->setWrapText(true);
                            } elseif ($key == "O") {
                                $sheet->setCellValue($key . $fila, $val);
                                $sheet->getStyle($key . $fila)->getAlignment()->setWrapText(true);
                            } elseif ($key == "K") {
                                $sheet->setCellValue($key . $fila, $val);
                                if ($val >= 59) {
                                    $sheet->getStyle($key . $fila)->applyFromArray([
                                        'fill' => [
                                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                            'startColor' => ['rgb' => 'FFC7CE'],
                                        ],
                                    ]);
                                }
                            } else {
                                $sheet->setCellValue($key . $fila, $val);
                            }
                            $sheet->getStyle($key . $fila)->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                ],
                                'font' => [
                                    'size' => 8,
                                ],
                            ]);
                        }
                        $fila++;
                    }

                    // Crear un archivo Excel temporal único
                    $tempExcel = tempnam(sys_get_temp_dir(), 'excel_') . "_{$index}.xlsx";
                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spread, 'Xlsx');
                    $writer->save($tempExcel);

                    // Agregar el archivo al ZIP con un nombre único
                    $zip->addFile($tempExcel, "Planilla ".$nombreTecnico->apellidos." ".$nombreTecnico->nombres." ".$fechaCompleta.".xlsx");
                }

                $zip->close();

                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="Planillas.zip"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                readfile($zipFile);

                // Limpiar archivos temporales
                unlink($zipFile);
                foreach (glob(sys_get_temp_dir() . "/excel_*") as $tempExcelFile) {
                    unlink($tempExcelFile);
                }
                exit;
            }
        }else if($expExcel == null && $expPdf == "on") {

            $zip = new ZipArchive();
            $zipFile = tempnam(sys_get_temp_dir(), 'Planillas') . '.zip';

            $htmlTemplate = "
                <style>
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        font-family: Arial, sans-serif;
                        font-size: 8px;
                        table-layout: auto;
                    }
                    th, td {
                        border: 1px solid black;
                        padding: 4px;
                        text-align: left;
                        vertical-align: top;
                    }
                    .header {
                        font-weight: bold;
                        background-color: #f2f2f2;
                    }
                    .observaciones {
                        height: 60px;
                        text-align: left;
                        vertical-align: top;
                        padding-left: 4px;
                    }
                    .title {
                        font-weight: bold;
                        background-color: lightgrey;
                    }
                    .orden-col {
                        width: 7%;
                    }
                    .barrio-col{
                        width: 7%;
                    }
                    .usuario-col{
                        width: 10%;
                    }
                </style>";

            if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                foreach ($arrayExcel as $index => $val) {

                    $mpdf = new Mpdf();
                    $html = $htmlTemplate;

                    foreach ($val as $values) {

                        $queryInspector = tbl_insp_cali::where('id', $values['P'])->first();

                        // Extraer información específica de cada campo
                        $partesDireccionUsuario = explode("//", $values['G']);
                        $partesDireccion = explode("1)Dir: ", $partesDireccionUsuario[0]);
                        $direccion = $partesDireccion[1];

                        $partesUsuario = explode("2)Usu: ", $partesDireccionUsuario[1]);
                        $usuario = $partesUsuario[1];

                        $partesCatMedUlt = explode("//", $values['I']);
                        $categoria = explode(" ", $partesCatMedUlt[0]);
                        $medidor = explode(" ", $partesCatMedUlt[1]);
                        $ultRevision = explode(" ", $partesCatMedUlt[2]);

                        // Construir HTML
                        $html .= "
                            <table>
                                <tr>
                                    <td class='title'>Orden:</td>
                                    <td>{$values['B']}</td>
                                    <td class='title'>Contrato:</td>
                                    <td>{$values['C']}</td>
                                    <td class='title'>Inspector:</td>
                                    <td>{$queryInspector->apellidos} {$queryInspector->nombres}</td>
                                    <td class='title'>Localidad:</td>
                                    <td>{$values['D']}</td>
                                    <td class='title'>Sub zona:</td>
                                    <td>{$values['E']}</td>
                                </tr>
                            </table>
                            <table>
                                <tr>
                                    <td class='title'>Barrio:</td>
                                    <td>{$values['F']}</td>
                                    <td class='title'>Dirección:</td>
                                    <td colspan='3'>{$direccion}</td>
                                    <td class='title'>Estado técnico</td>
                                    <td colspan='3'>{$values['H']}</td>
                                </tr>
                            </table>
                            <table>
                                <tr>
                                    <td class='title'>Usuario:</td>
                                    <td>{$usuario}</td>
                                    <td class='title'>Tipo categoría:</td>
                                    <td>{$categoria[1]}</td>
                                    <td class='title'># Medidor:</td>
                                    <td>{$medidor[1]}</td>
                                    <td class='title'>Fecha última revisión:</td>
                                    <td colspan='3'>{$ultRevision[2]}</td>
                                </tr>
                            </table>
                            <table>
                                <tr>
                                    <td class='title'>Tipo de trabajo:</td>
                                    <td>{$values['J']}</td>
                                    <td class='title'>Meses:</td>
                                    <td>{$values['K']}</td>
                                    <td class='title'>Fecha vence OT:</td>
                                    <td>{$values['L']}</td>
                                    <td class='title'>Fecha asignación o programación:</td>
                                    <td>{$values['M']}</td>
                                    <td class='title'>Jornada programación:</td>
                                    <td>{$values['N']}</td>
                                </tr>
                            </table>
                            <table>
                                <tr>
                                    <td class='title observaciones'>Observaciones solicitud o programación:</td>
                                    <td colspan='9' class='observaciones'>{$values['O']}</td>
                                </tr>
                            </table>
                            <hr>";
                    }

                    // Generar PDF para cada índice
                    $mpdf->WriteHTML($html);
                    $filename = "Planilla ".$queryInspector->apellidos." ".$queryInspector->nombres." ".$fechaCompleta.".pdf";
                    $zip->addFromString($filename, $mpdf->Output('', 'S')); // Agregar al ZIP
                }
                $zip->close();

                header('Content-Type: application/zip');
                header('Content-disposition: attachment; filename=Planillas.zip');
                readfile($zipFile);
                unlink($zipFile);
                exit;
            }
        }else if($expExcel == "on" && $expPdf == "on"){

            $zip = new ZipArchive();
            $zipFile = tempnam(sys_get_temp_dir(), 'Planillas') . '.zip';

            $htmlTemplate = "
                <style>
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        font-family: Arial, sans-serif;
                        font-size: 8px;
                        table-layout: auto;
                    }
                    th, td {
                        border: 1px solid black;
                        padding: 4px;
                        text-align: left;
                        vertical-align: top;
                    }
                    .header {
                        font-weight: bold;
                        background-color: #f2f2f2;
                    }
                    .observaciones {
                        height: 60px;
                        text-align: left;
                        vertical-align: top;
                        padding-left: 4px;
                    }
                    .title {
                        font-weight: bold;
                        background-color: lightgrey;
                    }
                    .orden-col {
                        width: 7%;
                    }
                    .barrio-col{
                        width: 7%;
                    }
                    .usuario-col{
                        width: 10%;
                    }
                </style>";

            if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                foreach($arrayExcel as $index => $values) {

                    $spread = new Spreadsheet();

                    $mpdf = new Mpdf();

                    $sheet = $spread->getActiveSheet();
                    $sheet->setTitle("Coordinacion RP");

                    $sheet->mergeCells('A1:O1');

                    $html = $htmlTemplate;

                    $fila = 3;

                    foreach ($values as $value) {

                        // consultamos los inspectores
                        $nombreTecnico = tbl_insp_cali::where('id', $value['P'])->first();

                        $arrayHeader = [
                            "A1" => "Asignación ".$nombreTecnico->apellidos." ".$nombreTecnico->nombres,
                            "A2" => "Item",
                            "B2" => "Orden",
                            "C2" => "Contrato",
                            "D2" => "Localidad",
                            "E2" => "Sub zona",
                            "F2" => "Barrio",
                            "G2" => "Direccion - Usuario",
                            "H2" => "ET",
                            "I2" => "Categoria - Medidor - Ultima revision",
                            "J2" => "TT",
                            "K2" => "Mes",
                            "L2" => "Fecha en que vence",
                            "M2" => "Fecha asig./ progra.",
                            "N2" => "Jornada",
                            "O2" => "Observaciones solicitud o programación",
                        ];

                        // Crear la primera imagen
                        $drawing1 = new Drawing();
                        $drawing1->setName('Logo');
                        $drawing1->setDescription('Logo de la asignación');
                        $drawing1->setPath('img/logo-ec-isotipo.png'); // Ruta de la primera imagen
                        $drawing1->setCoordinates('A1'); // Posiciona la imagen en A1
                        $drawing1->setOffsetX(10); // Ajusta el desplazamiento horizontal
                        $drawing1->setOffsetY(10); // Ajusta el desplazamiento vertical
                        $drawing1->setWidth(120); // Ancho de la primera imagen
                        $drawing1->setHeight(50); // Altura de la primera imagen
                        $drawing1->setWorksheet($sheet);

                        // Crear la segunda imagen
                        $drawing2 = new Drawing();
                        $drawing2->setName('Logo adicional');
                        $drawing2->setDescription('Logo adicional logo gdo');
                        $drawing2->setPath('img/gdo.png'); // Ruta de la segunda imagen
                        $drawing2->setCoordinates('O1'); // Mismo A1
                        $drawing2->setOffsetX(10); // Desplazamiento horizontal mayor para colocarla al final
                        $drawing2->setOffsetY(10); // Mantén el desplazamiento vertical similar al primero
                        $drawing2->setWidth(120); // Ancho de la segunda imagen
                        $drawing2->setHeight(50); // Altura de la segunda imagen
                        $drawing2->setWorksheet($sheet);

                        // Ajustar la altura de la fila
                        $sheet->getRowDimension(1)->setRowHeight(50);

                        // Alinear contenido de A1 si es necesario
                        $sheet->getStyle('A1')->getAlignment()->applyFromArray([
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ]);

                        $sheet->getStyle('O1')->getAlignment()->applyFromArray([
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ]);

                        foreach ($arrayHeader as $key => $header) {
                            // asignamos el rango de columnas a lque queremos aplicar el color asigado en $color
                            if ($key == "A1") {
                                $color = 'FFD9E6F5';#D9E6F5
                                $rango = 'A1:O1';
                                $size = 20;
                                $colorFont = 'FF000038';
                            }else{
                                $color = 'FF8DB4E2';
                                $rango = 'A2:O2';
                                $size = 12;
                                $colorFont = 'FFFFFFFF';
                            }

                            // asigamos el estilo al rango de celdas
                            $sheet->getStyle($rango)->applyFromArray([
                                'font' => [
                                    'bold' => true,
                                    'color' => ['argb' => $colorFont],
                                    'size' => $size,
                                ],
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['argb' => $color],
                                ],
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                ],
                            ]);

                            // asognamos los demas encabezados
                            $sheet->setCellValue($key, $header);

                            // sacamos solo la letra de la columna para dimensionar la columna
                            if (strpos($key, "2")) {
                                $key = explode("2", $key)[0];
                            }else {
                                $key = explode("1", $key)[0];
                            }

                            if ($key === 'G' || $key === 'I' || $key === 'O') {
                                $sheet->getColumnDimension($key)->setWidth(30); // Ancho personalizado
                            }else{
                                $sheet->getColumnDimension($key)->setAutoSize(true);
                            }
                        }

                        foreach ($value as $key => $val) {
                            if($key == 'P') continue;
                            if ($key == "G" || $key == "I") {
                                $valPartes = explode("//", $val);
                                $textoFinal = implode("\n", $valPartes);
                                $sheet->setCellValue($key . $fila, $textoFinal);
                                $sheet->getStyle($key . $fila)->getAlignment()->setWrapText(true);
                            } elseif ($key == "O") {
                                $sheet->setCellValue($key . $fila, $val);
                                $sheet->getStyle($key . $fila)->getAlignment()->setWrapText(true);
                            } elseif ($key == "K") {
                                $sheet->setCellValue($key . $fila, $val);
                                if ($val >= 59) {
                                    $sheet->getStyle($key . $fila)->applyFromArray([
                                        'fill' => [
                                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                            'startColor' => ['rgb' => 'FFC7CE'],
                                        ],
                                    ]);
                                }
                            } else {
                                $sheet->setCellValue($key . $fila, $val);
                            }
                            $sheet->getStyle($key . $fila)->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                ],
                                'font' => [
                                    'size' => 8,
                                ],
                            ]);
                        }

                        $partesDireccionUsuario = explode("//", $value['G']);
                        $partesDireccion = explode("1)Dir: ", $partesDireccionUsuario[0]);
                        $direccion = $partesDireccion[1];

                        $partesUsuario = explode("2)Usu: ", $partesDireccionUsuario[1]);
                        $usuario = $partesUsuario[1];

                        $partesCatMedUlt = explode("//", $value['I']);
                        $categoria = explode(" ", $partesCatMedUlt[0]);
                        $medidor = explode(" ", $partesCatMedUlt[1]);
                        $ultRevision = explode(" ", $partesCatMedUlt[2]);

                        // Construir HTML
                        $html .= "
                            <table>
                                <tr>
                                    <td class='title'>Orden:</td>
                                    <td>{$value['B']}</td>
                                    <td class='title'>Contrato:</td>
                                    <td>{$value['C']}</td>
                                    <td class='title'>Inspector:</td>
                                    <td>{$nombreTecnico->apellidos} {$nombreTecnico->nombres}</td>
                                    <td class='title'>Localidad:</td>
                                    <td>{$value['D']}</td>
                                    <td class='title'>Sub zona:</td>
                                    <td>{$value['E']}</td>
                                </tr>
                            </table>
                            <table>
                                <tr>
                                    <td class='title'>Barrio:</td>
                                    <td>{$value['F']}</td>
                                    <td class='title'>Dirección:</td>
                                    <td colspan='3'>{$direccion}</td>
                                    <td class='title'>Estado técnico</td>
                                    <td colspan='3'>{$value['H']}</td>
                                </tr>
                            </table>
                            <table>
                                <tr>
                                    <td class='title'>Usuario:</td>
                                    <td>{$usuario}</td>
                                    <td class='title'>Tipo categoría:</td>
                                    <td>{$categoria[1]}</td>
                                    <td class='title'># Medidor:</td>
                                    <td>{$medidor[1]}</td>
                                    <td class='title'>Fecha última revisión:</td>
                                    <td colspan='3'>{$ultRevision[2]}</td>
                                </tr>
                            </table>
                            <table>
                                <tr>
                                    <td class='title'>Tipo de trabajo:</td>
                                    <td>{$value['J']}</td>
                                    <td class='title'>Meses:</td>
                                    <td>{$value['K']}</td>
                                    <td class='title'>Fecha vence OT:</td>
                                    <td>{$value['L']}</td>
                                    <td class='title'>Fecha asignación o programación:</td>
                                    <td>{$value['M']}</td>
                                    <td class='title'>Jornada programación:</td>
                                    <td>{$value['N']}</td>
                                </tr>
                            </table>
                            <table>
                                <tr>
                                    <td class='title observaciones'>Observaciones solicitud o programación:</td>
                                    <td colspan='9' class='observaciones'>{$value['O']}</td>
                                </tr>
                            </table>
                            <hr>";

                        $fila++;
                    }

                    // Crear un archivo Excel temporal único
                    $tempExcel = tempnam(sys_get_temp_dir(), 'excel_') . "_reporte.xlsx";
                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spread, 'Xlsx');
                    $writer->save($tempExcel);

                    // Agregar el archivo al ZIP con un nombre único
                    $zip->addFile($tempExcel, "Planilla ".$nombreTecnico->apellidos." ".$nombreTecnico->nombres." ".$fechaCompleta.".xlsx");

                    // Generar PDF para cada índice
                    $mpdf->WriteHTML($html);
                    $filename = "Planilla ".$nombreTecnico->apellidos." ".$nombreTecnico->nombres." ".$fechaCompleta.".pdf";
                    $zip->addFromString($filename, $mpdf->Output('', 'S')); // Agregar al ZIP
                }

                $zip->close();

                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="Planillas.zip"');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                readfile($zipFile);

                // Limpiar archivos temporales
                unlink($zipFile);
                foreach (glob(sys_get_temp_dir() . "/excel_*") as $tempExcelFile) {
                    unlink($tempExcelFile);
                }

            }
        }
        return null;
    }

    public function asignarOrdCercania(){

        // consultamos todas las ordenes que esten asignadas para sacar el inspector y el subgrupo
        $queryAsignadas = asignadas::where('status', 1)->get();

        // creamos un array para almacenar el subgrupo y el inspector
        $arraySubGrupoInspector = [];
        $arraySubGrupo = [];

        foreach($queryAsignadas as $asignada){

            //con el nombre del municipio consultamos el municipio para sacar el id
            $queryMunicipio = tbl_localidades_municipio::where('nombre', $asignada->localidad)->first();

            // Consultamos primero el municipio
            $queryLugar = $queryMunicipio;
            $columnaLugar = 'id_mun';

            // Si no se encuentra el detalle por municipio, buscamos por barrio
            $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();

            if ($queryDetalle == null) {
                // Ahora buscamos por barrio si no encontramos el detalle por municipio
                $queryLugar = TblBarrios::where('barrio', $asignada->sector_operativo)->first();
                $columnaLugar = 'id_barrio';

                if ($queryLugar != null) {
                    $queryDetalle = TblGruposDetalle::where($columnaLugar, $queryLugar->id)->first();
                }
            }

            // Consultamos la tabla de subgrupos
            $querySubGrupos = TblSubgrupo::where('id', $queryDetalle->id_subGrupo)->first();

            if($asignada->codigo_tecnico != null){
                $arraySubGrupoInspector[] = [
                    'inspector' => $asignada->codigo_tecnico,
                    'subgrupo' => $querySubGrupos->subgrupo
                ];
            }else{
                $arraySubGrupo[] = [
                    'subgrupoSinAsignar' => $querySubGrupos->subgrupo,
                    'id' => $asignada->id
                ];
            }
        }

        if(empty($arraySubGrupoInspector)){
            echo 1;
            return;
        }

        $fechaActual = date('Y-m-d');

        $asignaciones = [];

        // 1. Agrupar inspectores por subgrupo
        $inspectoresPorSubgrupo = [];
        foreach ($arraySubGrupoInspector as $subgrupoInspector) {
            $subgrupo = $subgrupoInspector['subgrupo'];
            $inspector = $subgrupoInspector['inspector'];
            // Agrupamos los inspectores por subgrupo
            $inspectoresPorSubgrupo[$subgrupo][] = $inspector;
        }

        $counter = [];
        $asignaciones = [];

        // 3. Recorrer las órdenes (subgrupos sin inspector) y asignar un inspector de forma equitativa
        foreach ($arraySubGrupo as $orden) {
            $subgrupo = $orden['subgrupoSinAsignar'];
            if (isset($inspectoresPorSubgrupo[$subgrupo])) {
                // Inicializar el contador si aún no se ha creado para este subgrupo
                if (!isset($counter[$subgrupo])) {
                    $counter[$subgrupo] = 0;
                }
                // Obtener la lista de inspectores para este subgrupo
                $inspectores = $inspectoresPorSubgrupo[$subgrupo];
                // Asignar al inspector según el contador (round-robin)
                $assignedInspector = $inspectores[$counter[$subgrupo] % count($inspectores)];
                // Agregar la asignación a la orden
                $orden['inspector'] = $assignedInspector;
                $asignaciones[] = $orden;
                // Incrementar el contador para repartir equitativamente
                $counter[$subgrupo]++;
            }
        }

        foreach($asignaciones as $asignar){
            // consultamos el nombre del inspector con el id
            $queryInspectorAsignar = tbl_insp_cali::where('id', $asignar['inspector'])->first();

            $nombreInspectorAsignar = $queryInspectorAsignar->apellidos." ".$queryInspectorAsignar->nombres;

            // asignamos las ordenes a los inspectores
            asignadas::where('id', $asignar['id'])
                        ->where('status', 1)
                        ->update(['codigo_tecnico' => $asignar['inspector'], 'nom_inspector' => $nombreInspectorAsignar, 'fecha_asignacion_inspector' => $fechaActual]);
        }

        echo 2;
    }

    public function aplicacion(){

        $inspectors = tbl_insp_cali::all();

        return view('gestion.aplicacion', compact('inspectors'));
    }

    public function generarTablaAplication(){

    }

}
