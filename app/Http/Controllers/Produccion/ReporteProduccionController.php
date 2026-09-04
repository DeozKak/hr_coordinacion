<?php

namespace App\Http\Controllers\Produccion;

use App\Http\Controllers\Controller;
use App\Models\Nomina\TblNominaFechas;
use App\Models\Nomina\TblParametroPrecios;
use App\Models\Produccion\TblInspeccionIndustrial;
use App\Http\Requests\Produccion\ActualizarParametrosPreciosRequest;
use App\Http\Requests\Produccion\GuardarProduccionRequest;
use App\Http\Requests\Produccion\InspeccionIndustrialRequest;
use App\Http\Requests\Produccion\InsertarMetasRequest;
use App\Http\Requests\Produccion\ParametrosPreciosRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rmunate\Calendario\CalendarioColombia;

class ReporteProduccionController extends Controller
{
    public function diario()
    {
        $currentYear = date('Y') + 1;
        return view('reporteProduccion.diario', compact('currentYear'));
    }

    public function showEnero(Request $request)
    {
        return $this->getConsult($request, 1);
    }

    public function showFebrero(Request $request)
    {
        return $this->getConsult($request, 2);
    }

    public function showMarzo(Request $request)
    {
        return $this->getConsult($request, 3);
    }

    public function showAbril(Request $request)
    {
        return $this->getConsult($request, 4);
    }

    public function showMayo(Request $request)
    {
        return $this->getConsult($request, 5);
    }

    public function showJunio(Request $request)
    {
        return $this->getConsult($request, 6);
    }

    public function showJulio(Request $request)
    {
        return $this->getConsult($request, 7);
    }

    public function showAgosto(Request $request)
    {
        return $this->getConsult($request, 8);
    }

    public function showSeptiembre(Request $request)
    {
        return $this->getConsult($request, 9);
    }

    public function showOctubre(Request $request)
    {
        return $this->getConsult($request, 10);
    }

    public function showNoviembre(Request $request)
    {
        return $this->getConsult($request, 11);
    }

    public function showDiciembre(Request $request)
    {
        return $this->getConsult($request, 12);
    }

    public function getConsult(Request $request, $mes)
    {
        $anio = $request->query('anio');
        $month = str_pad($mes, 2, '0', STR_PAD_LEFT);
        $fechasParametro = [];

        $fechaInicioPattern = $anio . '-' . $month . '-01';
        $fechaFinPattern = $anio . '-' . $month . '-31';

        $parametroPrecios = TblParametroPrecios::where('fecha_inicio', '<=', $fechaFinPattern)
            ->where('fecha_fin', '>=', $fechaInicioPattern)
            ->first();

        if ($parametroPrecios == null) {
            $fechasParametro[] = [
                'res_metro' => 0,
                'res_norte' => 0,
                'res_cauca' => 0,
                'com_metro' => 0,
                'com_norte' => 0,
                'com_cauca' => 0,
                'inspeccion_industrial' => 0
            ];
        } else {
            $fechasParametro[] = [
                'res_metro' => $parametroPrecios->res_metro,
                'res_norte' => $parametroPrecios->res_norte,
                'res_cauca' => $parametroPrecios->res_cauca,
                'com_metro' => $parametroPrecios->com_metro,
                'com_norte' => $parametroPrecios->com_norte,
                'com_cauca' => $parametroPrecios->com_cauca,
                'inspeccion_industrial' => $parametroPrecios->inspeccion_industrial
            ];
        }

        $startDate = "$anio-$month-01";
        $endDate = date("Y-m-t", strtotime("$anio-$month-01"));

        $dates = [];
        $diasFestivos = [];
        $diasSabados = [];
        $consultaNomina = [];
        $cantidadInspeccionInd = [];
        $currentDate = new \DateTime($startDate);
        $endDateTime = new \DateTime($endDate);

        // consultamos la tabla de nomina con fechas
        $nominaFechas = TblNominaFechas::whereBetween('fecha', [$currentDate, $endDateTime])->get();

        foreach ($nominaFechas as $nominaFecha) {
            $consultaNomina[] = [
                'fechaNomina' => $nominaFecha->fecha,
                'proyeccion' => $nominaFecha->cantidad_proyectada,
            ];
        }

        $fechaDateTime = $currentDate;
        $fechaString = $fechaDateTime->format('Y-m');


        // consultamos la tabla de inspeccion industrial para traer la cantidad
        $inspeccionIndustrial = TblInspeccionIndustrial::where('fecha', $fechaString)->get();

        foreach ($inspeccionIndustrial as $inspeccion) {
            $cantidadInspeccionInd[] = [
                'cantidad' => $inspeccion->cantidad,
            ];
        }

        while ($currentDate <= $endDateTime) {
            $dateString = $currentDate->format('Y-m-d');

            $counts = DB::select("
                    SELECT
                        MAX(tblMun.id_zona) AS zona,
                        tbl_bitacora_contratos.CC_OPERARIO,
                        COUNT(DISTINCT tbl_bitacora_contratos.CC_OPERARIO) AS total_inspectores,

                        -- Contar residenciales por zona
                        COUNT(
                            CASE
                                WHEN tbl_bitacora_contratos.CATEGORIA = 'RESIDENCIAL' AND tblMun.id_zona = ?
                                THEN tbl_bitacora_contratos.id
                            END
                        ) AS count_residencial_zona_1,
                        COUNT(
                            CASE
                                WHEN tbl_bitacora_contratos.CATEGORIA = 'RESIDENCIAL' AND tblMun.id_zona = ?
                                THEN tbl_bitacora_contratos.id
                            END
                        ) AS count_residencial_zona_2,
                        COUNT(
                            CASE
                                WHEN tbl_bitacora_contratos.CATEGORIA = 'RESIDENCIAL' AND tblMun.id_zona = ?
                                THEN tbl_bitacora_contratos.id
                            END
                        ) AS count_residencial_zona_3,

                        -- Contar comerciales por zona
                        COUNT(
                            CASE
                                WHEN tbl_bitacora_contratos.CATEGORIA = 'COMERCIAL' AND tblMun.id_zona = ?
                                THEN tbl_bitacora_contratos.id
                            END
                        ) AS count_comercial_zona_1,
                        COUNT(
                            CASE
                                WHEN tbl_bitacora_contratos.CATEGORIA = 'COMERCIAL' AND tblMun.id_zona = ?
                                THEN tbl_bitacora_contratos.id
                            END
                        ) AS count_comercial_zona_2,
                        COUNT(
                            CASE
                                WHEN tbl_bitacora_contratos.CATEGORIA = 'COMERCIAL' AND tblMun.id_zona = ?
                                THEN tbl_bitacora_contratos.id
                            END
                        ) AS count_comercial_zona_3
                    FROM
                        tbl_bitacora_contratos
                    JOIN tbl_localidades_municipios AS tblMun
                    ON tblMun.nombre = tbl_bitacora_contratos.MUNICIPIO
                    WHERE tbl_bitacora_contratos.FECHA = ?
                    AND tbl_bitacora_contratos.state = ?
                    AND tbl_bitacora_contratos.TIPO_TRABAJO != ?
                     AND tbl_bitacora_contratos.TIPO_TRABAJO != ?
                    GROUP BY tbl_bitacora_contratos.CC_OPERARIO
                ", [1, 2, 3, 1, 2, 3, $dateString, 1, 'FI-29 revisión periódica línea matriz', 'FI-31 REVISIÓN NUEVA LINEA MATRIZ']);



            // Convertir la colección de $counts a un array
            $dates[] = [
                'fecha' => $dateString,
                'conteos' => $counts
            ];

            if (CalendarioColombia::date($dateString)->isHoliday()) {
                $diasFestivos[] = $dateString;
            }

            if (date('N', strtotime($dateString)) == 6) {
                $diasSabados[] = $dateString;
            }
            $currentDate->modify('+1 day');
        }

        $data = [
            'conteos' => $dates,
            'diasFestivos' => $diasFestivos,
            'diasSabados' => $diasSabados,
            'nomina' => $consultaNomina,
            'inspeccionIndustrial' => $cantidadInspeccionInd,
            'preciosParametros' => $fechasParametro
        ];

        return response()->json($data);
    }

    public function guardarProduccion(GuardarProduccionRequest $request)
    {
        $nuevaCant = $request->input('nuevaCant');
        $fecha = $request->input('fechaFila');

        if ($nuevaCant == "NaN") {
            $nuevaCant = 0;
        }

        $nomina = TblNominaFechas::where('fecha', $fecha)->first();
        if ($nomina) {
            $nomina->cantidad_proyectada = $nuevaCant;
        } else {
            $nomina = new TblNominaFechas();
            $nomina->cantidad_proyectada = $nuevaCant;
            $nomina->fecha = $fecha;
        }
        $insertar = $nomina->save();

        if ($insertar) {
            echo 1;
        } else {
            echo 2;
        }
    }

    public function inspeccionIndustrial(InspeccionIndustrialRequest $request)
    {
        $total = $request->input('totalFinal');
        $cantidad = $request->input('valor');
        $fecha = $request->input('fechaFila');

        if ($cantidad == "NaN" || $cantidad == NULL) {
            $cantidad = 0;
        }

        $fechaPartes = explode("-", $fecha);

        $fechaProcesada = $fechaPartes[0] . "-" . $fechaPartes[1];

        $inspIndustrial = TblInspeccionIndustrial::where('fecha', $fechaProcesada)->first();
        if ($inspIndustrial) {
            $inspIndustrial->cantidad = $cantidad;
            $inspIndustrial->total = $total;
        } else {
            $inspIndustrial = new TblInspeccionIndustrial();
            $inspIndustrial->fecha = $fechaProcesada;
            $inspIndustrial->cantidad = $cantidad;
            $inspIndustrial->total = $total;
            $inspIndustrial->metagyc = 0;
            $inspIndustrial->metagdo = 0;
        }
        $insertar = $inspIndustrial->save();

        if ($insertar) {
            echo 1;
        } else {
            echo 2;
        }
    }

    public function fechasProduccion()
    {
        // consultamos los precios parametrizados
        $fechaPrecios = TblParametroPrecios::orderBy('id', 'desc')->get();
        return view('reporteProduccion.registrarFechasNomina', compact('fechaPrecios'));
    }

    public function reporteConsolidado()
    {
        $meses = [
            '-01' => 'Enero',
            '-02' => 'Febrero',
            '-03' => 'Marzo',
            '-04' => 'Abril',
            '-05' => 'Mayo',
            '-06' => 'Junio',
            '-07' => 'Julio',
            '-08' => 'Agosto',
            '-09' => 'Septiembre',
            '-10' => 'Octubre',
            '-11' => 'Noviembre',
            '-12' => 'Diciembre'
        ];
        $currentYear = date('Y') + 1;
        return view('reporteProduccion.reporteConsolidado', compact('meses', 'currentYear'));
    }

    public function generarReporteConsolidado(Request $request)
    {
        $anio = $request->input('anio');

        // Generamos una fecha en formato año mes y día
        $fechaInicial = $anio . '-01-01';
        $fechaFinal = $anio . '-12-31';

        // Arreglo con todos los meses en español
        $meses = [
            'Enero',
            'Febrero',
            'Marzo',
            'Abril',
            'Mayo',
            'Junio',
            'Julio',
            'Agosto',
            'Septiembre',
            'Octubre',
            'Noviembre',
            'Diciembre'
        ];

        // Convertir el arreglo de meses a una colección
        $mesesCollection = collect($meses);

        // Consulta general
        $counts = DB::select("
                SELECT
                    MONTH(tbl_bitacora_contratos.FECHA) AS mes_numero,
                    COUNT(CASE WHEN tbl_bitacora_contratos.TIPO_TRABAJO IN (?, ?, ?, ?) THEN tbl_bitacora_contratos.id END) AS total_rp,
                    COUNT(CASE WHEN tbl_bitacora_contratos.TIPO_TRABAJO = ? THEN tbl_bitacora_contratos.id END) AS total_previas,
                    COUNT(CASE WHEN tbl_bitacora_contratos.CATEGORIA = ? THEN tbl_bitacora_contratos.id END) AS total_residencial,
                    COUNT(CASE WHEN tbl_bitacora_contratos.CATEGORIA = ? THEN tbl_bitacora_contratos.id END) AS total_comercial,
                    COUNT(DISTINCT tbl_bitacora_contratos.CC_OPERARIO) AS total_operarios
                FROM
                    tbl_bitacora_contratos
                WHERE
                    tbl_bitacora_contratos.FECHA BETWEEN ? AND ?
                    AND tbl_bitacora_contratos.state = ?
                    AND tbl_bitacora_contratos.TIPO_TRABAJO != ?
                GROUP BY
                    mes_numero
            ", [
            'RP 10444',
            'RP 12161',
            'SA 12163',
            'SA 12164',
            'RN 12162',
            'RESIDENCIAL',
            'COMERCIAL',
            $fechaInicial,
            $fechaFinal,
            1,
            'FI-29 revisión periódica línea matriz'
        ]);


        // Convertir los resultados a un arreglo asociativo
        $countsArray = collect($counts)->keyBy('mes_numero')->toArray();

        // consultamos las inspecciones
        $inspecciones = TblInspeccionIndustrial::whereBetween('fecha', [$anio . '-01', $anio . '-12'])->get();

        // Convertir los resultados de inspecciones a un arreglo asociativo
        $inspeccionesArray = $inspecciones->keyBy('fecha')->toArray();

        // Crear un arreglo para el resultado final con todos los meses
        $result = [];

        foreach ($mesesCollection as $index => $mes) {
            $mesNumero = $index + 1;

            $mesNumeroFormateado = str_pad($mesNumero, 2, '0', STR_PAD_LEFT);
            $claveMes = $anio . '-' . $mesNumeroFormateado;

            if (isset($inspeccionesArray[$claveMes])) {
                $totalInspecciones = $inspeccionesArray[$claveMes]['cantidad'];
                $total = $inspeccionesArray[$claveMes]['total'];
                $metagyc = $inspeccionesArray[$claveMes]['metagyc'];
                $metagdo = $inspeccionesArray[$claveMes]['metagdo'];
            } else {
                $totalInspecciones = 0;
                $total = 0;
                $metagyc = 0;
                $metagdo = 0;
            }

            $result[] = [
                'nombre_mes' => $mes,
                'total_residencial' => isset($countsArray[$mesNumero]) ? $countsArray[$mesNumero]->total_residencial : 0,
                'total_comercial' => isset($countsArray[$mesNumero]) ? $countsArray[$mesNumero]->total_comercial : 0,
                'total_inspecciones' => $totalInspecciones,
                'total_inspectores' => isset($countsArray[$mesNumero]) ? $countsArray[$mesNumero]->total_operarios : 0,
                'total' => $total,
                'metaGyc' => $metagyc,
                'metaGdo' => $metagdo,
                'total_rp' => isset($countsArray[$mesNumero]) ? $countsArray[$mesNumero]->total_rp : 0,
                'total_previas' => isset($countsArray[$mesNumero]) ? $countsArray[$mesNumero]->total_previas : 0
            ];
        }
        return response()->json($result);
    }


    public function insertarMetas(InsertarMetasRequest $request)
    {

        $fecha = $request->input('anioMes');
        $metaGyc = $request->input('metagyc');
        $metaGdo = $request->input('metagdo');

        $datoMetas = TblInspeccionIndustrial::where('fecha', $fecha)->first();
        if ($datoMetas) {
            if ($metaGyc == null) {
                $datoMetas->metagyc = $datoMetas['metagyc'];
            } else {
                $datoMetas->metagyc = $metaGyc;
            }
            if ($metaGdo == null) {
                $datoMetas->metagdo = $datoMetas['metagdo'];
            } else {
                $datoMetas->metagdo = $metaGdo;
            }
        } else {
            $datoMetas = new TblInspeccionIndustrial();
            $datoMetas->fecha = $fecha;
            $datoMetas->cantidad = 0;
            $datoMetas->total = 0;
            if ($metaGyc == null) {
                $datoMetas->metagyc = 0;
            } else {
                $datoMetas->metagyc = $metaGyc;
            }

            if ($metaGdo == null) {
                $datoMetas->metagdo = 0;
            } else {
                $datoMetas->metagdo = $metaGdo;
            }
        }
        $insertar = $datoMetas->save();

        if ($insertar) {
            echo 1;
        } else {
            echo 2;
        }
    }

    public function generarReportePorMes(Request $request)
    {
        $fecha = $request->input('data');

        $anio = substr($fecha, 0, 4);

        // realizamos la misma consulta de los precios parametrizados
        $parametroPrecios = TblParametroPrecios::where('fecha_inicio', 'like', $anio . '%')
        ->where('fecha_fin', 'like', $anio . '%')
        ->first();

        if ($parametroPrecios == null) {
            $fechasParametro[] = [
                'res_metro' => 0,
                'res_norte' => 0,
                'res_cauca' => 0,
                'com_metro' => 0,
                'com_norte' => 0,
                'com_cauca' => 0,
                'inspeccion_industrial' => 0
            ];
        } else {
            $fechasParametro[] = [
                'res_metro' => $parametroPrecios->res_metro,
                'res_norte' => $parametroPrecios->res_norte,
                'res_cauca' => $parametroPrecios->res_cauca,
                'com_metro' => $parametroPrecios->com_metro,
                'com_norte' => $parametroPrecios->com_norte,
                'com_cauca' => $parametroPrecios->com_cauca,
                'inspeccion_industrial' => $parametroPrecios->inspeccion_industrial
            ];
        }

        $counts = DB::select(
            "SELECT
                    COUNT(CASE WHEN tbl_bitacora_contratos.CATEGORIA = 'RESIDENCIAL' AND tblMun.id_zona = ? THEN tbl_bitacora_contratos.id END) AS count_residencial_zona_1,
                    COUNT(CASE WHEN tbl_bitacora_contratos.CATEGORIA = 'RESIDENCIAL' AND tblMun.id_zona = ? THEN tbl_bitacora_contratos.id END) AS count_residencial_zona_2,
                    COUNT(CASE WHEN tbl_bitacora_contratos.CATEGORIA = 'RESIDENCIAL' AND tblMun.id_zona = ? THEN tbl_bitacora_contratos.id END) AS count_residencial_zona_3,
                    COUNT(CASE WHEN tbl_bitacora_contratos.CATEGORIA = 'COMERCIAL' AND tblMun.id_zona = ? THEN tbl_bitacora_contratos.id END) AS count_comercial_zona_1,
                    COUNT(CASE WHEN tbl_bitacora_contratos.CATEGORIA = 'COMERCIAL' AND tblMun.id_zona = ? THEN tbl_bitacora_contratos.id END) AS count_comercial_zona_2,
                    COUNT( CASE WHEN tbl_bitacora_contratos.CATEGORIA = 'COMERCIAL' AND tblMun.id_zona = ? THEN tbl_bitacora_contratos.id END) AS count_comercial_zona_3
                    FROM
                        tbl_bitacora_contratos
                        JOIN tbl_localidades_municipios AS tblMun ON tblMun.nombre = tbl_bitacora_contratos.MUNICIPIO
                    WHERE
                        tbl_bitacora_contratos.FECHA LIKE ? AND tbl_bitacora_contratos.state = ? AND tbl_bitacora_contratos.TIPO_TRABAJO != ?
                    GROUP BY
                        tbl_bitacora_contratos.CC_OPERARIO AND tblMun.id_zona",
            [1, 2, 3, 1, 2, 3, '%' . $fecha . '%', 1, 'FI-29 revisión periódica línea matriz']
        );

        if (!empty($counts)) {
            $result = [
                'residencial' => [
                    'zona_1' => $counts[0]->count_residencial_zona_1,
                    'zona_2' => $counts[0]->count_residencial_zona_2,
                    'zona_3' => $counts[0]->count_residencial_zona_3
                ],
                'comercial' => [
                    'zona_1' => $counts[0]->count_comercial_zona_1,
                    'zona_2' => $counts[0]->count_comercial_zona_2,
                    'zona_3' => $counts[0]->count_comercial_zona_3
                ],
                'fechas' => $fechasParametro
            ];
        } else {
            $result = [
                'residencial' => [
                    'zona_1' => 0,
                    'zona_2' => 0,
                    'zona_3' => 0
                ],
                'comercial' => [
                    'zona_1' => 0,
                    'zona_2' => 0,
                    'zona_3' => 0
                ],
                'fechas' => $fechasParametro
            ];
        }
        return response()->json($result);
    }

    public function guardarFechasParametros(ParametrosPreciosRequest $request)
    {
        $fechaInicio = $request->input('fechaPrecioInicio');
        $fechaFin = $request->input('fechaPrecioFin');
        $metroRes = intval($request->input('metroRes'));
        $norteRes = intval($request->input('norteRes'));
        $caucaRes = intval($request->input('caucaRes'));

        $metroCom = intval($request->input('metroCom'));
        $norteCom = intval($request->input('norteCom'));
        $caucaCom = intval($request->input('caucaCom'));
        $inspeccionInd = intval($request->input('inspeccionInd'));


        // Validación de fechas vacías
        if ($fechaInicio == "" || $fechaFin == "") {
            return response()->json(['status' => 1]); // Fechas son obligatorias
        }

        // Validación de fecha de inicio mayor que fecha de fin
        if ($fechaFin < $fechaInicio) {
            return response()->json(['status' => 2]); // La fecha de inicio no puede ser mayor a la de fin
        }

        // Validación de datos numéricos
        if (
            !is_numeric($metroRes) || !is_numeric($norteRes) || !is_numeric($caucaRes) ||
            !is_numeric($metroCom) || !is_numeric($norteCom) || !is_numeric($caucaCom) || !is_numeric($inspeccionInd)
        ) {
            return response()->json(['status' => 3]); // Datos inválidos
        }

        // Validar si las fechas cruzan con los registros existentes
        $fechaParametros = DB::select(
            "SELECT * FROM tbl_parametro_precios
            WHERE (fecha_inicio <= ? AND fecha_fin >= ?)",
            [$fechaFin, $fechaInicio]
        );

        if ($fechaParametros) {
            // Ya existe un registro en ese rango de fechas
            $resultado = $fechaParametros[0];
            return response()->json([
                'status' => 4, // Ya hay un registro
                'id' => $resultado->id,
                'fecha_inicio' => $resultado->fecha_inicio,
                'fecha_fin' => $resultado->fecha_fin
            ]);
        } else {
            // Realizamos el registro de las fechas
            $insertar = TblParametroPrecios::create([
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'res_metro' => $metroRes,
                'res_norte' => $norteRes,
                'res_cauca' => $caucaRes,
                'com_metro' => $metroCom,
                'com_norte' => $norteCom,
                'com_cauca' => $caucaCom,
                'inspeccion_industrial' => $inspeccionInd
            ]);

            if ($insertar) {
                return response()->json(['status' => 5]);
            } else {
                return response()->json(['status' => 6]);
            }
        }
    }

    public function actualizarFechasParametros(ActualizarParametrosPreciosRequest $request)
    {
        $id = $request->input('id');
        $fechaInicio = $request->input('fechaPrecioInicio');
        $fechaFin = $request->input('fechaPrecioFin');
        $metroRes = intval($request->input('metroRes'));
        $norteRes = intval($request->input('norteRes'));
        $caucaRes = intval($request->input('caucaRes'));

        $metroCom = intval($request->input('metroCom'));
        $norteCom = intval($request->input('norteCom'));
        $caucaCom = intval($request->input('caucaCom'));
        $inspeccionInd = intval($request->input('inspeccionInd'));

        // Validación de fechas vacías
        if ($fechaInicio == "" || $fechaFin == "") {
            return response()->json(['status' => 1]); // Fechas son obligatorias
        }

        // Validación de fecha de inicio mayor que fecha de fin
        if ($fechaFin < $fechaInicio) {
            return response()->json(['status' => 2]); // La fecha de inicio no puede ser mayor a la de fin
        }

        if (
            !is_numeric($metroRes) || !is_numeric($norteRes) || !is_numeric($caucaRes) ||
            !is_numeric($metroCom) || !is_numeric($norteCom) || !is_numeric($caucaCom) || !is_numeric($inspeccionInd)
        ) {
            return response()->json(['status' => 3]); // Datos inválidos
        }

        // Validar si las fechas cruzan con los registros existentes
        $fechaParametros = DB::select(
            "SELECT * FROM tbl_parametro_precios
            WHERE (fecha_inicio <= ? AND fecha_fin >= ?) AND id != ?",
            [$fechaFin, $fechaInicio, $id]
        );

        if ($fechaParametros) {
            // Ya existe un registro en ese rango de fechas
            $resultado = $fechaParametros[0];
            return response()->json([
                'status' => 4, // Ya hay un registro
                'id' => $resultado->id,
                'fecha_inicio' => $resultado->fecha_inicio,
                'fecha_fin' => $resultado->fecha_fin
            ]);
        } else {
            // Obtenemos el registro actual para comparar
            $registroActual = TblParametroPrecios::find($id);
            // Comparamos los valores antes de actualizar
            if (
                $registroActual->fecha_inicio == $fechaInicio &&
                $registroActual->fecha_fin == $fechaFin &&
                $registroActual->res_metro == $metroRes &&
                $registroActual->res_norte == $norteRes &&
                $registroActual->res_cauca == $caucaRes &&
                $registroActual->com_metro == $metroCom &&
                $registroActual->com_norte == $norteCom &&
                $registroActual->com_cauca == $caucaCom &&
                $registroActual->inspeccion_industrial == $inspeccionInd
            ) {
                return response()->json(['status' => 7]);
            } else {
                // Realizamos el registro de las fechas
                $actualizar = TblParametroPrecios::where('id', $id)->update([
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'res_metro' => $metroRes,
                    'res_norte' => $norteRes,
                    'res_cauca' => $caucaRes,
                    'com_metro' => $metroCom,
                    'com_norte' => $norteCom,
                    'com_cauca' => $caucaCom,
                    'inspeccion_industrial' => $inspeccionInd
                ]);

                if ($actualizar) {
                    return response()->json(['status' => 5]); // Actualización exitosa
                } else {
                    return response()->json(['status' => 6]); // Error en la actualización
                }
            }
        }
    }
}
