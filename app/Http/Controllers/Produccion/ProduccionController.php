<?php

namespace App\Http\Controllers\Produccion;

use App\Http\Controllers\Controller;
use App\Jobs\CorreoProduccion;
use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Produccion\TblProduccionCorte;
use App\Models\Produccion\TblProduccionHistorico;
use App\Models\Produccion\TblProduccionZona;
use App\Models\TblInspCali;
use App\Models\Zonificacion\TblLocalidadesMunicipio;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Rmunate\Calendario\CalendarioColombia;

class ProduccionController extends Controller
{
    public function index(Request $request)
    {

        $arrayInspectores = [];
        $cortes = TblProduccionCorte::all();
        if ($request->id) {
            $corte = TblProduccionCorte::find($request->id);
        } else {
            $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-MM-DD'
            $fecha_resta_un_dia = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));
            $corte = TblProduccionCorte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
                ->where('fecha_fin', '>=', $fecha_resta_un_dia)
                ->first();
        }

        $warning = null;
        $error = false;
        // sacar cortes activos
        if ($corte === null && !$error) {
            $error = true;
            $warning = 'No hay corte activo';
            return view('produccion.index', ['produccionInspector' => "produccionInspector", 'contratosCategoria' => "contratosCategoria", 'conteoContratosPorZona' => " conteoContratosPorZona", 'corte' => $corte, 'warning' => $warning, 'cortes' => $cortes, 'arrayInspectores' => $arrayInspectores]);
        }
        // Guardar el ID del corte actual en la sesión
        session(['corte_actual_id' => $corte->id]);

        // sacar contratos del corte activo
        $contratosCorte = TblBitacoraContrato::where('FECHA', '>=', $corte->fecha_inicio)
            ->where('FECHA', '<=', $corte->fecha_fin)->where('state', '=', 1)
            ->get();

        if (count($contratosCorte->toArray()) === 0 && !$error) {
            $error = true;
            $warning = 'No hay contratos en el corte activo';
        }

        // Obtenemos los inspectores con producción dentro del rango de fechas especificado
        $inspectores = TblInspCali::whereHas('contratos', function ($query) use ($corte) {
            $query->where('FECHA', '>=', $corte->fecha_inicio)
                ->where('FECHA', '<=', $corte->fecha_fin)
                ->where('state', 1)
                ->whereNotIn('TIPO_TRABAJO', [
                    'FI-29 revisión periódica línea matriz',
                    'FI-31 REVISIÓN NUEVA LINEA MATRIZ'
                ]);
        })->orderby('apellidos')->get();

        if (count($inspectores->toArray()) === 0 && !$error) {
            $error = true;
            $warning = 'No hay inspectores activos';
        }
        // sacar produccion de cada inspector
        $produccionInspector = array();
        foreach ($inspectores as $inspector) {

            $contratos = TblBitacoraContrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->where('FECHA', '>=', $corte->fecha_inicio)
                ->where('FECHA', '<=', $corte->fecha_fin)
                ->where('state', '=', 1)
                ->whereNotIn('TIPO_TRABAJO', [
                    'FI-29 revisión periódica línea matriz',
                    'FI-31 REVISIÓN NUEVA LINEA MATRIZ'
                ])
               // ->where('CATEGORIA', '=', 'COMERCIAL')
                ->count();

            // Añadir datos al array
            $produccionInspector[] = [
                'nombres' => $inspector->apellidos,
                'contratos' => $contratos, // Conserva el total de contratos en esta variable
                'cedula' => $inspector->cedula
            ];
        }

        $municipiosNoEncontrados = array();

        // Verificador de municipios no encontrados
        // Obtener los municipios de tbl_bitacora_contrato
        $municipiosContratos = TblBitacoraContrato::select('MUNICIPIO')
            ->where('FECHA', '>=', $corte->fecha_inicio)
            ->where('FECHA', '<=', $corte->fecha_fin)
            ->where('state', '=', 1)
            ->whereNotIn('TIPO_TRABAJO', [
                'FI-29 revisión periódica línea matriz',
                'FI-31 REVISIÓN NUEVA LINEA MATRIZ'
            ])
            ->distinct()
            ->get()
            ->pluck('MUNICIPIO');

        // Obtener los municipios de tbl_localidades_municipios
        $municipiosLocalidades = TblLocalidadesMunicipio::select('nombre')
            ->distinct()
            ->get()
            ->pluck('nombre');

        // Comparar los municipios
        $municipiosNoEncontrados = $municipiosContratos->diff($municipiosLocalidades);

        if ($municipiosNoEncontrados->isEmpty()) {
        } else {
            foreach ($municipiosNoEncontrados as $municipio) {

                $municipiosNoEncontrados = collect(array_unique($municipiosNoEncontrados->toArray()));
            }
        }

        if ($error) {
            return view('produccion.index', ['produccionInspector' => $produccionInspector, 'corte' => $corte, 'warning' => $warning, 'cortes' => $cortes, 'inspectores' => $inspectores]);
        }

        return view('produccion.index', compact('produccionInspector', 'corte', 'warning', 'municipiosNoEncontrados', 'cortes', 'inspectores'));
    }

    public function getCorteData(Request $request)
    {

        $corteId = $request->id;
        $inspectorCC = $request->inspector_cc;
        // Buscar el corte seleccionado por ID
        $corte = TblProduccionCorte::find($corteId);

        if (!$corte) {
            return response()->json(['error' => 'Corte no encontrado'], 404);
        }

        // Obtener los inspectores y sus inspecciones para el corte seleccionado
        if ($inspectorCC) {
            $inspectores = TblInspCali::whereIn('cedula', $inspectorCC)->get();
        } else {
            // Obtenemos los inspectores con producción dentro del rango de fechas especificado
            $inspectores = TblInspCali::whereHas('contratos', function ($query) use ($corte) {
                $query->where('FECHA', '>=', $corte->fecha_inicio)
                    ->where('FECHA', '<=', $corte->fecha_fin)
                    ->where('state', 1)
                    ->whereNotIn('TIPO_TRABAJO', [
                        'FI-29 revisión periódica línea matriz',
                        'FI-31 REVISIÓN NUEVA LINEA MATRIZ'
                    ]);
            })->get();
        }

        $produccionInspector = [];

        foreach ($inspectores as $inspector) {
            $contratos = TblBitacoraContrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->where('FECHA', '>=', $corte->fecha_inicio)
                ->where('FECHA', '<=', $corte->fecha_fin)
                ->where('state', '=', 1)
                ->whereNotIn('TIPO_TRABAJO', [
                    'FI-29 revisión periódica línea matriz',
                    'FI-31 REVISIÓN NUEVA LINEA MATRIZ',
                ])
                //->where('CATEGORIA', '=', 'COMERCIAL')
                ->count();

            $produccionInspector[] = [
                'nombres' => $inspector->apellidos,
                'contratos' => $contratos,
                'cedula' => $inspector->cedula
            ];
        }

        return response()->json([
            'produccionInspector' => $produccionInspector,
            'nombreCorte' => $corte->nombre . " " . explode("-", $corte->fecha_inicio)[0] . "-" . explode("-", $corte->fecha_fin)[0]
        ]);
    }

    public function getCorteTotalData(Request $request)
    {

        $cortes_id = $request->cortes;

        if (!$cortes_id) {
            return response()->json(['error' => 'Corte no encontrado'], 404);
        }

        $cortes = TblProduccionCorte::whereIn('id', $cortes_id)->get();

        $results = [];

        foreach ($cortes as $corte) {
            $contratos = TblBitacoraContrato::where('FECHA', '>=', $corte->fecha_inicio)
                ->where('FECHA', '<=', $corte->fecha_fin)
                ->where('state', '=', 1)
                ->whereNotIn('TIPO_TRABAJO', [
                    'FI-29 revisión periódica línea matriz',
                    'FI-31 REVISIÓN NUEVA LINEA MATRIZ',])
                ->count();

            $results[] = [
                'id' => $corte->id,
                'nombreCorte' => $corte->nombre . " " . explode("-", $corte->fecha_inicio)[0] . "-" . explode("-", $corte->fecha_fin)[0],
                'totalContratos' => $contratos,
            ];
        }

        return response()->json($results);
    }

    public function detallesCorte($id)
    {
        session(['id_corte' => $id]);

        return $this->detalles();
    }

    public function detalles()
    {

        $municipios = TblLocalidadesMunicipio::all();
        if (session('id_corte')) {
            $corte = TblProduccionCorte::find(session('id_corte'));
            $id_corte = session('id_corte');
            if ($corte) { // Verificación adicional
                session(['fecha_inicio' => $corte->fecha_inicio]);
                return view('produccion.detalles', compact('municipios', 'corte', 'id_corte'));
            } else {
                // Manejar el caso en que $corte es nulo (mostrar un mensaje de error, redirigir, etc.)
                return redirect()->back()->with('error', 'No se encontró el corte seleccionado.');
            }
        }
        $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-MM-DD'

        $fecha_resta_un_dia = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));
        $corte = TblProduccionCorte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
            ->where('fecha_fin', '>=', $fecha_resta_un_dia)
            ->first();
        return view('produccion.detalles', compact('municipios', 'corte'));
    }

    public function datosDetalles(Request $request)
    {
        $f = [];
        if (session('id_corte') || $request->idCorteDetalles) {
            $idCorte = session('id_corte') ?? $request->idCorteDetalles;
            $corte = TblProduccionCorte::find($idCorte);

            $exist = TblProduccionHistorico::where('id_corte', $corte->id)->exists();

            if ($exist) {
                $historico = TblProduccionHistorico::where('id_corte', $corte->id)->first();
                //$response = json_decode($historico->data);
                session()->forget('id_corte');
                session()->save();

                session()->put('corteEnviar', $corte);

                //return response()->json($response);
            }
        } else {
            $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-MM-DD'
            //$fecha_actual = "2025-05-30";

            $fecha_resta_un_dia = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));

            $corte = TblProduccionCorte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
                ->where('fecha_fin', '>=', $fecha_resta_un_dia)
                ->first();

            session()->put('corteEnviar', $corte);
        }

        $diasIntermedios = $this->DiasIntermedios($corte);

        if ($diasIntermedios == null) {
            return response()->json(['error' => 'No hay corte activo']);
        }

        $fechaInicio = new DateTime($corte->fecha_inicio);
        $fechaFin = new DateTime($corte->fecha_fin);
        $fechaFin->modify('+1 day');
        $interval = new DateInterval('P1D'); // Intervalo de 1 día
        $periodo = new DatePeriod($fechaInicio, $interval, $fechaFin);

        $fechasIntermedias = [];
        foreach ($periodo as $fecha) {
            $fechasIntermedias[] = $fecha->format('Y-m-d');
        }
        // sacar inspectores
        //$inspectores = TblInspCali::orderBy('apellidos', 'asc')->get();
        $inspectores = TblInspCali::whereHas('contratos', function ($query) use ($corte) {
            $query->where('FECHA', '>=', $corte->fecha_inicio)
                ->where('FECHA', '<=', $corte->fecha_fin)
                ->where('state', 1);
        })->orderBy('apellidos', 'asc')->get();
        $cedulas = $inspectores->pluck('cedula');

        // 1. REALIZAR UNA ÚNICA CONSULTA PARA TODOS LOS CONTRATOS
        $todosLosContratos = TblBitacoraContrato::whereIn('CC_OPERARIO', $cedulas)
            ->where('state', 1)
            ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
            ->select(
                'CC_OPERARIO',
                'FECHA',
                'CATEGORIA',
                '4_RECINTOS',
                'TIPO_TRABAJO',
                'diseno_especial'
            )->get();
        //guardar colección de contratos por inspector
        $contratosPorInspector = $todosLosContratos->groupBy('CC_OPERARIO');
        //consulta para saber fechas que no cuentan doble
        $sqlProHis = TblProduccionHistorico::where('id_corte', $corte['id'])->first();
        //decodificar JSON de festivos y Sabados
        $jsonDecode = json_decode($sqlProHis->no_dobles_festivos, true);
        $jsonDecodeSabados = json_decode($sqlProHis->dobles_sabados, true);
        //Inicializar Arrays
        $sabados = array();
        $produccionInspector = array();

        // Generar todas las fechas en el rango
        $fechaInicio = Carbon::parse($corte->fecha_inicio);
        $fechaFin = Carbon::parse($corte->fecha_fin);
        $fechas = [];

        // Calcular la duración del corte en minutos
        $duracionCorte = $fechaInicio->diffInMinutes($fechaFin);

        // Calcular la fecha de inicio y fin del rango de 1 mes antes y 1 mes después
        $fechaInicioRango = $fechaInicio->copy()->subMonth();
        $fechaFinRango = $fechaFin->copy();

        // Generar una clave única para la caché basada en las fechas del rango
        $cacheKeyRango = 'dias_festivos_rango_' . $fechaInicioRango->format('Ymd') . '_' . $fechaFinRango->format('Ymd');

        // Verificar si los días festivos en el rango ya están en caché
        //$diasFestivosRango = "";
        $diasFestivosRango = Cache::get($cacheKeyRango);

        /* Cache::forget($cacheKeyRango); */

        if (!$diasFestivosRango) {
            $diasFestivosRango = [];
            $festivosExtraordinarios = [
                '2026-07-13',
                // '2026-XX-YY' puedes ir agregando más en el futuro
            ];
            // Calcular y almacenar los días festivos en el rango de fechas
            for ($date = $fechaInicioRango; $date->lte($fechaFinRango); $date->addDay()) {
                $fechas[$date->format('Y-m-d')] = "";

                // Filtrar las fechas para mantener solo las originales
                $fechas = array_filter($fechas, function ($fecha) use ($fechaInicio, $fechaFin) {
                    return $fecha >= $fechaInicio->format('Y-m-d') && $fecha <= $fechaFin->format('Y-m-d');
                }, ARRAY_FILTER_USE_KEY);

                // 2. VALIDAR LIBRERÍA O ARREGLO DE EXCEPCIONES
                if (CalendarioColombia::date($date->format('Y-m-d'))->isHoliday() || in_array($date->format('Y-m-d'), $festivosExtraordinarios)) {
                    $diasFestivosRango[] = $date->format('Y-m-d');
                }
            }
            // Guardar los días festivos en el rango en caché por un tiempo determinado (por ejemplo, 24 horas)
            Cache::put($cacheKeyRango, $diasFestivosRango, $duracionCorte);
        } else {
            for ($date = $fechaInicio; $date->lte($fechaFin); $date->addDay()) {
                $fechas[$date->format('Y-m-d')] = ""; // Inicializa todas las fechas con 0 contratos
            }
        }
        $fechasPlantilla = $fechas;


        // sacar produccion de cada inspector
        foreach ($inspectores as $inspector) {
            $fechaInicio = Carbon::parse($corte->fecha_inicio);
            $fechaFin = Carbon::parse($corte->fecha_fin);
           // dd($fechasPlantilla);
            $fechas = $fechasPlantilla;
            $contadorFestivosDomingos = 0;
            $contadorDiasSabados = 0;
            //inicializar variables contadores
            $contadorDiseñosEspeciales = null;
            $contadorMatrices = null;
            $contadorFestivos = null;
            $contratos4recintos = null;
            $contadorComerciales = null;
            $contadorNuevas = null;
            $sumaInspecciones = 0;
            $referenciaInicio = $fechaInicio->copy();
            $referenciaFin = $fechaFin->copy();

            // Obtenemos solo los contratos del inspector actual, sin consultar la BD de nuevo.
            $contratosDelInspectorActual = $contratosPorInspector->get($inspector->cedula, collect());

            // Si el inspector no tiene contratos, saltamos a la siguiente iteración.
            if ($contratosDelInspectorActual->isEmpty()) {
                continue;
            }

            // 1. REEMPLAZO para: Contratos por día
            $contratosPorDia = $contratosDelInspectorActual
                ->groupBy(function($contrato) {
                    // Agrupamos por fecha en formato Y-m-d
                    return Carbon::parse($contrato->FECHA)->format('Y-m-d');
                })
                ->map(function($group) {
                    // Contamos los contratos por cada grupo de fecha
                    return (object)[
                        'fecha' => $group->first()->FECHA,
                        'total_contratos' => $group->whereNotIn('TIPO_TRABAJO', [
                            'FI-29 revisión periódica línea matriz',
                            'FI-31 REVISIÓN NUEVA LINEA MATRIZ',
                        ])->count()
                    ];
                });
            /*if($inspector->cedula == '94313913'){
                dd($contratosPorDia);
            }*/
            // 2. REEMPLAZO para: Contratos por categoría
            $contratosPorCategoria = $contratosDelInspectorActual
                ->groupBy('CATEGORIA')
                ->map(function($group, $categoria) {
                    return (object)[
                        'CATEGORIA' => $categoria,
                        'total_contratos' => $group->count()
                    ];
                });
            // 3. REEMPLAZO para: Contratos por recinto
            $contratosPorRecinto = $contratosDelInspectorActual
                ->groupBy('4_RECINTOS')
                ->map(function($group, $recinto) {
                    return (object)[
                        '4_RECINTOS' => $recinto,
                        'total_contratos' => $group->count()
                    ];
                });
            // 4. REEMPLAZO para: Contratos Nuevas (RN 12162)
            $contratosNuevas = $contratosDelInspectorActual
                ->where('TIPO_TRABAJO', 'RN 12162')
                ->count();

            // 5. REEMPLAZO para: Matrices
            $matrices = $contratosDelInspectorActual
                ->whereIn('TIPO_TRABAJO', [
                    'FI-29 revisión periódica línea matriz',
                    'FI-31 REVISIÓN NUEVA LINEA MATRIZ'
                ])
                ->count();

            // 6. REEMPLAZO para: Diseños Especiales
            $diseñosEspeciales = $contratosDelInspectorActual
                ->where('diseno_especial', 1)
                ->count();

            if ($diseñosEspeciales > 0) {
                $contadorDiseñosEspeciales = $diseñosEspeciales;
            }

            if ($matrices > 0) {
                $contadorMatrices = $matrices;
            }

            if ($contratosNuevas > 0) {
                $contadorNuevas = $contratosNuevas;
            }

            if ($jsonDecode != null) {
                foreach ($jsonDecode['noDoblesFestivos'] as &$registro) {
                    foreach ($registro['datos']['totalInspecciones'] as $totalInsp) {
                        if ($registro['datos']['cc_inspector'] == $inspector->cedula) {
                            $contadorFestivosDomingos += $totalInsp['total_contratos'];
                        }
                    }
                }
            }

            if ($jsonDecodeSabados != null) {
                foreach ($jsonDecodeSabados['doblesSabados'] as &$registro) {
                    foreach ($registro['datos']['totalInspecciones'] as $totalInsp) {
                        if ($registro['datos']['cc_inspector'] == $inspector->cedula) {
                            $contadorDiasSabados += intval($totalInsp['total_contratos']);
                        }
                    }
                }
            }

            // contadores dobles contratos
            foreach ($contratosPorDia as $contrato) {
                foreach ($diasFestivosRango as $festivo) {
                    if ($festivo == $contrato->fecha) {
                        $contadorFestivos += $contrato->total_contratos;
                        $f[$contrato->fecha] = $contrato->total_contratos;
                    }
                }
                $fechas[$contrato->fecha] = $contrato->total_contratos;
                $sumaInspecciones += $contrato->total_contratos;
            }


            $sabadosDobles = $this->calcularDobles($referenciaInicio, $referenciaFin, $inspector, $diasFestivosRango, $corte->dobles, $sqlProHis);

            foreach ($contratosPorCategoria as $contrato_C) {
                if ($contrato_C->CATEGORIA === 'COMERCIAL') {
                    $contadorComerciales = $contrato_C->total_contratos;
                }
            }

            foreach ($contratosPorRecinto as $contrato_R) {
                if ($contrato_R->{'4_RECINTOS'} != 'NO') {
                    $subtotal = 0;
                    $subtotal = $contrato_R->total_contratos * $contrato_R->{'4_RECINTOS'};
                    $contratos4recintos = $contratos4recintos + $subtotal;
                }
            }

            $contratos4recintos = $contratos4recintos / 4;
            $contratos4recintosRedondeado = floor($contratos4recintos);

            if ($contratos4recintosRedondeado == intval($contratos4recintosRedondeado)) {
                $contratos4recintosRedondeado = intval($contratos4recintosRedondeado);
            }

            if ($contratos4recintosRedondeado === 0) {
                $contratos4recintosRedondeado = null;
            }

            // Crear el array final para el inspector
            $datosInspector = [
                'cedula' => $inspector->cedula,
                'nombres' => $inspector->apellidos . ' ' . $inspector->nombres,
            ];

            $contadorDiasLaborados = 0;
            // Agregar los contratos por fecha
            foreach ($fechas as $fecha => $total_contratos) {
                $datosInspector[$fecha] = $total_contratos;
                if ($total_contratos > 0) {
                    $contadorDiasLaborados++;
                }
            }

            $totalFestivos = $contadorFestivos + $sabadosDobles['contadorDiasSabados'];

            if ($totalFestivos === 0) {
                $totalFestivos = null;
            }

            $sabados[] = [
                'datos' => $sabadosDobles['sabadosdobles']
            ];
            if ($contadorDiasLaborados === 0) {
            }
            $datosInspector['sub_total'] = $sumaInspecciones;
            if ($sumaInspecciones === 0 && $inspector->state === 0) {
                continue;
            }
            $datosInspector['matrices'] = $contadorMatrices;

            $datosInspector['festivos'] = $totalFestivos - $contadorFestivosDomingos + $contadorDiasSabados;

            $datosInspector['diseños_especiales'] = $contadorDiseñosEspeciales;
            $datosInspector['4_recintos'] = $contratos4recintosRedondeado;
            $datosInspector['comerciales'] = $contadorComerciales;
            $datosInspector['total'] =
                $datosInspector['comerciales'] +
                $datosInspector['4_recintos'] +
                $datosInspector['festivos'] +
                $datosInspector['sub_total'] +
                $datosInspector['matrices'] +
                $datosInspector['diseños_especiales'];
            $datosInspector['nuevas'] = $contadorNuevas;
            $datosInspector['dias_laborados'] = $contadorDiasLaborados;
            $datosInspector['promedio'] = number_format($datosInspector['sub_total'] / $datosInspector['dias_laborados'], 1);
            $datosInspector['meta'] = $corte->meta;
            $datosInspector['porcentaje_meta'] = '%' . number_format(($datosInspector['sub_total'] / $datosInspector['meta']) * 100, 2);
            //dd($f);
            $produccionInspector[] = $datosInspector;

        }

        $sqlHistorico = TblProduccionHistorico::where('id_corte', $corte->id)->first();

        $jsonNoDobles = json_decode($sqlHistorico->no_dobles, true);
        // Si json_decode devuelve null, usará un array vacío ([]) en su lugar.
        $decoded_sabados = json_decode($sqlHistorico->dobles_sabados, true) ?? [];
        $jsonDobles_sabados = array_values($decoded_sabados);
        //dd($jsonDobles_sabados,$sabados);
        if ($jsonNoDobles != null) {
            foreach ($sabados as $sabadoIndex => &$sabado) {
                foreach ($sabado['datos'] as $datoIndex => $dato) {
                    foreach ($jsonNoDobles as $datos) {
                        foreach ($datos as $item) {
                            // Comprobamos si el inspector y la fecha coinciden
                            if ($dato['cc_inspector'] == $item['datos']['cc_inspector'] && in_array($dato['fecha'], $item['datos']['fechas'])) {
                                // Si se cumple la condición, eliminamos el dato en la posición correspondiente
                                unset($sabado['datos'][$datoIndex]);
                            }
                        }
                    }
                }
                // Re-indexamos los datos después de la eliminación
                $sabado['datos'] = array_values($sabado['datos']);
            }
            // Re-indexamos el array principal de $sabados
            $sabados = array_values($sabados);
        }

        $reponse = [
            'diasIntermedios' => $diasIntermedios,
            'produccionInspector' => $produccionInspector,
            'diasFestivos' => $diasFestivosRango,
            'sabadodobles' => $sabados,
            'fechasIntermedias' => $fechasIntermedias,
            'corte' => $corte->id,
            'sabadosDoblesManuales' => $jsonDobles_sabados,
        ];
        //dd($reponse);

        $exist = TblProduccionHistorico::where('id_corte', $corte->id)->exists();

        if ($exist) {
            $historico = TblProduccionHistorico::where('id_corte', $corte->id)->first();
            $historico->data = json_encode($reponse);
            $historico->save();
        } else {
            $historico = new TblProduccionHistorico();
            $historico->data = json_encode($reponse);
            $historico->id_corte = $corte->id;
            $historico->save();
        }
        $reponse = json_decode($historico->data);

        return response()->json($reponse);
    }

    public function DiasIntermedios($corte)
    {
        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $fechaActual = date('Y-m-d');

        if ($corte != null) {
            $fechaInicio = new DateTime($corte->fecha_inicio);
            $fechaFin = new DateTime($corte->fecha_fin);
            $fechaFin->modify('+1 day');
        } else {
            return null;
        }

        $interval = new DateInterval('P1D'); // Intervalo de 1 día
        $periodo = new DatePeriod($fechaInicio, $interval, $fechaFin);

        $diasIntermedios = array();
        foreach ($periodo as $fecha) {
            $nombreDia = $diasSemana[$fecha->format('w')];
            $numeroDia = $fecha->format('d');
            $nombreMes = $meses[$fecha->format('n')];


            $diasIntermedios[] =
                [
                    'dias' => $numeroDia,
                    'nombreDia' => $nombreDia,
                    'nombreMes' => $nombreMes
                ];
        }
        return $diasIntermedios;
    }

    public function calcularDobles($fechaInicio, $fechaFin, $inspector, $diasFestivos, $valDobles,$sqlHistorico)
    {
        // Inicializar variables para contadores
        $contadorDiasSabados = 0;
        $sabadosdobles = array();

        // Obtenemos solo los contratos del rango que sean sábados
        $contratosSabado = TblBitacoraContrato::where('CC_OPERARIO', $inspector->cedula)
            ->where('state', 1)
            ->whereBetween('FECHA', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
            ->whereNotIn('TIPO_TRABAJO', [
                'FI-29 revisión periódica línea matriz',
                'FI-31 REVISIÓN NUEVA LINEA MATRIZ',
            ])
            ->select('FECHA', 'HORA_INICIO', 'HORA_FINAL')
            ->get()
            ->filter(function ($contrato) {
                // Filtrar solo para quedarnos con los sábados (día 6)
                return \Carbon\Carbon::parse($contrato->FECHA)->dayOfWeekIso === 6;
            });

        // Agrupamos los contratos de sábado por su fecha exacta
        $contratosPorSabado = $contratosSabado->groupBy('FECHA');

        $jsonSabadosDobles = json_decode($sqlHistorico->dobles_sabados, true);
        $jsonNoDobles = json_decode($sqlHistorico->no_dobles, true);

        foreach ($contratosPorSabado as $fechaSabado => $contratos) {
            // Verificamos que el sábado no sea un festivo general
            if (in_array($fechaSabado, $diasFestivos)) {
                continue;
            }

            // 1. Agregar los sábados manuales si existen en el JSON de dobles_sabados
            if ($jsonSabadosDobles != null) {
                foreach ($jsonSabadosDobles as $datos) {
                    foreach ($datos as $item) {
                        if ($item['datos']['cc_inspector'] == $inspector->cedula) {
                            foreach ($item['datos']['totalInspecciones'] as $sabado) {
                                if ($sabado['fecha'] == $fechaSabado) {
                                    $sabadosdobles[] = [
                                        'fecha' => $sabado['fecha'],
                                        'cc_inspector' => $item['datos']['cc_inspector']
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            // 2. Verificar exclusiones manuales del JSON de no_dobles
            $esNoDoble = false;
            if ($jsonNoDobles != null) {
                foreach ($jsonNoDobles as $datos) {
                    foreach ($datos as $item) {
                        if ($inspector->cedula == $item['datos']['cc_inspector'] && in_array($fechaSabado, $item['datos']['fechas'])) {
                            $esNoDoble = true;
                            break 2;
                        }
                    }
                }
            }

            // Si no fue excluido manualmente, verificamos la regla de duración y cantidad
            if (!$esNoDoble) {
                $contratosValidos = 0;

                foreach ($contratos as $contrato) {
                    if (!empty($contrato->HORA_INICIO) && !empty($contrato->HORA_FINAL)) {
                        $horaInicioInspeccion = \Carbon\Carbon::parse($contrato->HORA_INICIO);
                        $horaFinalInspeccion = \Carbon\Carbon::parse($contrato->HORA_FINAL);

                        // Si la hora final es menor que la de inicio, asumimos que cruzó la medianoche
                        if ($horaFinalInspeccion->lt($horaInicioInspeccion)) {
                            $horaFinalInspeccion->addDay();
                        }

                        // Validamos que la diferencia sea ESTRÍCTAMENTE MAYOR a 20 minutos
                        if ($horaInicioInspeccion->diffInMinutes($horaFinalInspeccion) > 20) {
                            $contratosValidos++;
                        }
                    }
                }

                // Regla final: si hizo más de 6 inspecciones válidas, cuenta doble
                if ($contratosValidos > $valDobles) {
                    $doblesCalculados = $contratosValidos - $valDobles;
                    $contadorDiasSabados += $doblesCalculados;

                    $sabadosdobles[] = [
                        'fecha' => $fechaSabado,
                        'cc_inspector' => $inspector->cedula,
                        'totalContratosSabado' => $doblesCalculados
                    ];
                }
            }
        }

        return [
            'contadorDiasSabados' => $contadorDiasSabados,
            'sabadosdobles' => $sabadosdobles
        ];
    }

    public function detallesDiario($fecha, $inspector)
    {
        $corte = session('corteEnviar');

        $contratosDia = TblBitacoraContrato::selectRaw("tbl_bitacora_contratos.id, CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS nombre_completo, tbl_bitacora_contratos.CC_OPERARIO, tbl_bitacora_contratos.MUNICIPIO, tbl_bitacora_contratos.FECHA, tbl_bitacora_contratos.No_ACTA, tbl_bitacora_contratos.TIPO_TRABAJO, tbl_bitacora_contratos.CONTRATO, tbl_bitacora_contratos.ORDEN_TRABAJO, tbl_bitacora_contratos.ORDEN_EXT, tbl_bitacora_contratos.CATEGORIA, tbl_bitacora_contratos.RESULTADO_CIERRE, tbl_bitacora_contratos.HORA_INICIO, tbl_bitacora_contratos.HORA_FINAL, tbl_bitacora_contratos.DURACION_INSP,
        tbl_bitacora_contratos.`4_RECINTOS`,tbl_bitacora_contratos.state,tbl_bitacora_contratos.diseno_especial,tbl_bitacora_contratos.vence")
            ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_contratos.CC_OPERARIO')
            ->where('tbl_bitacora_contratos.CC_OPERARIO', '=', $inspector)
            ->where('tbl_bitacora_contratos.FECHA', '=', $fecha)
            ->get();

        $contadores = TblBitacoraContrato::selectRaw("PRIORIDAD, COUNT(*) AS total")
            ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_contratos.CC_OPERARIO')
            ->where('tbl_bitacora_contratos.CC_OPERARIO', '=', $inspector)
            ->where('tbl_bitacora_contratos.FECHA', '=', $fecha)
            ->groupBy('tbl_bitacora_contratos.PRIORIDAD')
            ->get();

        // Consultar la tabla `tbl_produccion_historico` usando el id del corte
        $sqlProHis = TblProduccionHistorico::where('id_corte', $corte['id'])->first();

        $jsonNoDobles = json_decode($sqlProHis->no_dobles, true);
        $jsonNoDoblesHoliday = json_decode($sqlProHis->no_dobles_festivos, true);
        $jsonDoblesSabados = json_decode($sqlProHis->dobles_sabados, true);
        $contratosDoblesSabadoos = json_decode($sqlProHis->data, true);

        $flag = false;
        /* if ($jsonNoDobles != null) {
             foreach ($jsonNoDobles as $datos) {
                 foreach ($datos as $item) {
                     if ($inspector == $item['datos']['cc_inspector'] && in_array($fecha, $item['datos']['fechas'])) {
                         $flag = true;
                     }
                 }
             }
         }*/

        $flagHolidays = false;
        if ($jsonNoDoblesHoliday != null) {
            foreach ($jsonNoDoblesHoliday as $datos) {
                foreach ($datos as $item) {
                    if ($inspector == $item['datos']['cc_inspector']) {
                        foreach ($item['datos']['totalInspecciones'] as $item2) {
                            if ($item2['fecha'] == $fecha) {
                                $flagHolidays = true;
                            }
                        }
                    }
                }
            }
        }

        $totalContratos = 0;
        $arrayDoblesSabados = [];
        if ($jsonDoblesSabados != null) {
            foreach ($jsonDoblesSabados as $datos) {
                foreach ($datos as $item) {
                    if ($inspector == $item['datos']['cc_inspector']) {
                        foreach ($item['datos']['totalInspecciones'] as $item2) {
                            if ($item2['fecha'] == $fecha) {
                                $flagDoblesSabados = true;
                                $arrayDoblesSabados[] = [
                                    $item2['total_contratos'],
                                    $flagDoblesSabados
                                ];
                            }
                        }
                    }
                }
            }
        }

        foreach ($contratosDoblesSabadoos['sabadodobles'] as $item) {
            foreach ($item['datos'] as $val) {
                if ($val['fecha'] == $fecha && $val['cc_inspector'] == $inspector) {
                    if (isset($val['totalContratosSabado'])) {
                        $totalContratos = $val['totalContratosSabado'];
                    }
                }
            }
        }

        return response()->json([
            $contratosDia,
            $flag,
            $flagHolidays,
            $arrayDoblesSabados,
            $totalContratos,
            $contadores->toArray(),
        ]);
    }

    public function ActualizarDetallesDiario(Request $request, $id)
    {
        $datos = null;
        $datos = $request->payload;
        if ($datos['prop'] === null || $datos['newValue'] === null) {
            return response()->json(['message' => 'Campo vacio']);
        }
        try {
            $contrato = TblBitacoraContrato::find($id);
            $contrato->{$datos['prop']} = $datos['newValue'];
            $contrato->save();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el contrato']);
        }
        return response()->json(['message' => 'OK']);
    }

    public function eliminarDetallesDiario($id)
    {

        $contrato = TblBitacoraContrato::find($id);
        if ($contrato->state === 0) {
            $contrato->state = 1;
            $contrato->save();
            return response()->json(['message' => 'OK']);
        }
        if ($contrato->state === 1) {
            $contrato->state = 0;
            $contrato->save();
            return response()->json(['message' => 'OK']);
        }
    }

    public function diseñoEspecial($id)
    {
        $contrato = TblBitacoraContrato::find($id);

        if ($contrato) {
            // Alternar el valor de diseño_especial
            $contrato->diseno_especial = !$contrato->diseno_especial;
            $contrato->save();

            return response()->json(['success' => true, 'diseño_especial' => $contrato->diseno_especial]);
        }

        return response()->json(['success' => false, 'message' => 'Contrato no encontrado']);
    }

    public function insertarContrato(Request $request)
    {
        $fechaString = $request->data[4];
        $ccOperario = $request->data[2];
        $contrato = new TblBitacoraContrato();
        $nomInspector = TblInspCali::select('apellidos', 'nombres')->where('cedula', '=', $ccOperario)->first();
        // Convertir la fecha a un formato adecuado (suponiendo dd-mm-yy)
        $fecha = Carbon::createFromFormat('d-m-y', $fechaString)->format('d-m-Y');
        $fechaDB = Carbon::createFromFormat('d-m-y', $fechaString)->format('Y-m-d');

        // Proceder con la consulta
        $resultados = $this->consultarBitacora($fecha, $ccOperario);
        if ($resultados === null) {
            return response()->json(['error' => 'No se encontró bitácora para asociar.']);
        }
        try {
            $contrato->CC_OPERARIO = $request->data[2];
            $contrato->MUNICIPIO = $request->data[3];
            $contrato->FECHA = $fechaDB;
            $contrato->No_ACTA = $request->data[5];
            $contrato->TIPO_TRABAJO = $request->data[6];
            $contrato->CONTRATO = $request->data[7];
            $contrato->ORDEN_TRABAJO = $request->data[8];
            $contrato->ORDEN_EXT = $request->data[9];
            $contrato->CATEGORIA = $request->data[10];
            $contrato->RESULTADO_CIERRE = $request->data[11];
            $contrato->HORA_INICIO = $request->data[12];
            $contrato->HORA_FINAL = $request->data[13];
            $contrato->DURACION_INSP = $request->data[14];
            $contrato->{'4_RECINTOS'} = $request->data[15];
            $contrato->id_bitacora = $resultados->id;
            $contrato->state = 1;
            $contrato->save();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al insertar el contrato']);
        }
        // Obtener los usuarios que deben recibir la notificación
        $usuarioLog = Auth::user();

        CorreoProduccion::dispatch($request->data[7], $usuarioLog->name, $fechaDB, $nomInspector);
        // Enviar la notificación a cada usuario
        return response()->json(['ok' => 'Insertado correctamente.']);
    }

    public function consultarBitacora($fecha, $ccOperario)
    {
        // Consultar la tabla tbl_insp_cali para obtener el nombre del supervisor
        $inspector = TblInspCali::select('users.name AS supervisor')
            ->join('users', 'users.id', '=', 'tbl_insp_cali.SUPERVISOR')
            ->where('tbl_insp_cali.cedula', '=', $ccOperario)
            ->first();

        if (!$inspector) {
            // Manejar el caso donde no se encuentra el supervisor
            return ['error' => 'Supervisor no encontrado.'];
        }
        $nombreSupervisor = $inspector->supervisor;
        $nombreSupervisorSinEspacios = str_replace(' ', '', $nombreSupervisor);

        // Consultar la tabla tbl_bitacora_archivo para obtener los registros que coincidan con la fecha
        $bitacoras = TblBitacoraArchivo::select('id', 'nombre_archivo')->get();

        foreach ($bitacoras as $bitacora) {
            $nombreArchivo = $bitacora->nombre_archivo;

            // Extraer la fecha y nombre del supervisor del nombre del archivo
            // Asumiendo el formato "Bitacora Valle_dd-mm-yyyy____dd-mm-yyyy Supervisor"
            if (preg_match('/Bitacora Valle_(\d{2}-\d{2}-\d{4})____(\d{2}-\d{2}-\d{4}) (.+)$/', $nombreArchivo, $matches)) {

                $fechaInicio = $matches[1];
                $fechaFin = $matches[2];
                $supervisor = $matches[3];
                $supervisorSinEspacios = str_replace(' ', '', $supervisor);

                // Verificar si la fecha del request está dentro del rango de fechas del archivo y el supervisor coincide
                $fechaInicioCarbon = Carbon::createFromFormat('d-m-Y', $fechaInicio);
                $fechaFinCarbon = Carbon::createFromFormat('d-m-Y', $fechaFin);
                $fechaCarbon = Carbon::createFromFormat('d-m-Y', $fecha);

                if ($fechaCarbon->between($fechaInicioCarbon, $fechaFinCarbon) && $supervisorSinEspacios == $nombreSupervisorSinEspacios) {

                    return $bitacora;
                }
            }
        }

        return ['error' => 'Bitácora no encontrada.'];
    }

    public function guardarNoDobles(Request $request)
    {
        // Obtener datos desde la sesión y la solicitud
        $corte = session('corteEnviar');
        $fecha = $request->input('fecha');
        $inspector = $request->input('ccInspector');

        // Consultar la tabla `tbl_produccion_historico` usando el id del corte
        $sqlProHis = TblProduccionHistorico::where('id_corte', $corte['id'])->first();

        // Decodificar el campo `no_dobles` para manipularlo como un array asociativo
        $corteHisJson = json_decode($sqlProHis->no_dobles, true);

        // Si `noDobles` aún no contiene datos, inicializar el array
        if (!isset($corteHisJson['noDobles'])) {
            $corteHisJson['noDobles'] = [];
        }

        // Variable para rastrear si ya existe el inspector
        $inspectorEncontrado = false;

        // Recorrer `noDobles` para buscar el inspector y agregar la nueva fecha en el mismo registro
        foreach ($corteHisJson['noDobles'] as &$registro) {
            if ($registro['datos']['cc_inspector'] == $inspector) {
                // Si encontramos el inspector, agregar la fecha al array de fechas si no está duplicada
                if (!in_array($fecha, $registro['datos']['fechas'])) {
                    $registro['datos']['fechas'][] = $fecha;
                }
                $inspectorEncontrado = true;
                break;
            }
        }

        // Si el inspector no fue encontrado, agregar un nuevo registro con el array de fechas
        if (!$inspectorEncontrado) {
            $nuevoRegistro = [
                'datos' => [
                    'cc_inspector' => $inspector,
                    'fechas' => [$fecha] // Inicia con un array que contiene la fecha actual
                ]
            ];

            // Agregar el nuevo registro al array de `noDobles`
            $corteHisJson['noDobles'][] = $nuevoRegistro;
        }

        // Codificar el array actualizado como JSON y guardarlo en la base de datos
        $sqlProHis->no_dobles = json_encode($corteHisJson);
        $sqlProHis->save();
    }

    public function contarDobles(Request $request)
    {
        $corte = session('corteEnviar');
        $fecha = $request->input('fecha');
        $inspector = $request->input('ccInspector');

        $sqlProHis = TblProduccionHistorico::where('id_corte', $corte['id'])->first();

        $corteHisJson = json_decode($sqlProHis->no_dobles, true);

        foreach ($corteHisJson['noDobles'] as &$datos) {
            if ($inspector == $datos['datos']['cc_inspector']) {
                foreach ($datos['datos']['fechas'] as $fechaKey => $fechaExistente) {
                    if ($fecha == $fechaExistente) {
                        unset($datos['datos']['fechas'][$fechaKey]);
                    }
                }
                // Reindexamos el array de fechas para evitar índices huecos
                $datos['datos']['fechas'] = array_values($datos['datos']['fechas']);
            }
        }

        $sqlProHis->no_dobles = json_encode($corteHisJson);
        $sqlProHis->save();
    }

    public function storeNotDoublesHolidays(Request $request)
    {
        // Recibimos las variables
        $corte = session('corteEnviar');
        $inspector = $request->input('ccInspector');
        $fecha = $request->input('fecha');

        // Consultar la tabla `tbl_produccion_historico` usando el id del corte
        $sqlProHis = TblProduccionHistorico::where('id_corte', $corte['id'])->first();

        // Obtener el total de inspecciones para la fecha y el inspector especificado
        $contratosDomingo = TblBitacoraContrato::where('FECHA', $fecha)
            ->where('state', '=', 1)
            ->selectRaw('DATE(FECHA) as fecha, COUNT(*) as total_contratos')
            ->groupBy('fecha')
            ->where('CC_OPERARIO', '=', $inspector)
            ->first(); // Usamos `first()` en lugar de `get()` para obtener un solo resultado

        // Convertir el resultado en un array para incluirlo en `totalInspecciones`
        $inspeccionData = [
            'fecha' => $contratosDomingo->fecha,
            'total_contratos' => $contratosDomingo->total_contratos
        ];

        // Decodificar el campo `no_dobles_festivos` para manipularlo como un array asociativo
        $corteHisJson = json_decode($sqlProHis->no_dobles_festivos, true);

        // Si `noDoblesFestivos` aún no contiene datos, inicializar el array
        if (!isset($corteHisJson['noDoblesFestivos'])) {
            $corteHisJson['noDoblesFestivos'] = [];
        }

        // Variable para rastrear si ya existe el inspector
        $inspectorEncontrado = false;

        // Recorrer `noDoblesFestivos` para buscar el inspector
        foreach ($corteHisJson['noDoblesFestivos'] as &$registro) {
            if ($registro['datos']['cc_inspector'] == $inspector) {
                // Si el inspector ya existe, verifica si la fecha está en `totalInspecciones`
                $fechaExiste = false;

                foreach ($registro['datos']['totalInspecciones'] as $inspeccion) {
                    if ($inspeccion['fecha'] == $fecha) {
                        $fechaExiste = true;
                        break;
                    }
                }

                // Si la fecha no existe, agregarla al array `totalInspecciones`
                if (!$fechaExiste) {
                    $registro['datos']['totalInspecciones'][] = $inspeccionData;
                }

                $inspectorEncontrado = true;
                break;
            }
        }

        // Si el inspector no fue encontrado, agregar un nuevo registro con el array de fechas
        if (!$inspectorEncontrado) {
            $nuevoRegistro = [
                'datos' => [
                    'cc_inspector' => $inspector,
                    'totalInspecciones' => [$inspeccionData], // Nuevo array con la primera inspección
                ]
            ];

            // Agregar el nuevo registro al array de `noDoblesFestivos`
            $corteHisJson['noDoblesFestivos'][] = $nuevoRegistro;
        }

        // Codificar el array actualizado como JSON y guardarlo en la base de datos
        $sqlProHis->no_dobles_festivos = json_encode($corteHisJson);
        $sqlProHis->save();
        return response()->json(['success' => true]);
    }

    public function countDoublesHolidays(Request $request)
    {
        $corte = session('corteEnviar');
        $fecha = $request->input('fecha');
        $inspector = $request->input('ccInspector');

        $sqlProHis = TblProduccionHistorico::where('id_corte', $corte['id'])->first();

        $corteHisJson = json_decode($sqlProHis->no_dobles_festivos, true);

        foreach ($corteHisJson['noDoblesFestivos'] as &$datos) {
            if ($inspector == $datos['datos']['cc_inspector']) {
                foreach ($datos['datos']['totalInspecciones'] as $key => $fechaExistente) {
                    if ($fecha == $fechaExistente['fecha']) {
                        unset($datos['datos']['totalInspecciones'][$key]);
                    }
                }
                // Reindexamos el array de fechas para evitar índices huecos
                $datos['datos']['totalInspecciones'] = array_values($datos['datos']['totalInspecciones']);
            }
        }

        $sqlProHis->no_dobles_festivos = json_encode($corteHisJson);
        $sqlProHis->save();
        return response()->json(['success' => true]);
    }

    public function countDoublesSaturday(Request $request)
    {
        $corte = session('corteEnviar');
        $ccInspector = $request->input('ccInspector');
        $fecha = $request->input('fecha');
        $diasContados = $request->input('diasContados');

        $sqlProHis = TblProduccionHistorico::where('id_corte', $corte['id'])->first();

        $inspeccionData = [
            'fecha' => $fecha,
            'total_contratos' => $diasContados,
        ];

        $corteHisJson = json_decode($sqlProHis->dobles_sabados, true);

        if (!isset($corteHisJson['doblesSabados'])) {
            $corteHisJson['doblesSabados'] = [];
        }

        // Variable para rastrear si ya existe el inspector
        $inspectorEncontrado = false;

        foreach ($corteHisJson['doblesSabados'] as &$registro) {
            if ($registro['datos']['cc_inspector'] == $ccInspector) {
                // Si el inspector ya existe, verifica si la fecha está en `totalInspeccionesContadas`
                foreach ($registro['datos']['totalInspecciones'] as $inspeccion) {
                    if ($inspeccion['fecha'] == $fecha) {
                        $inspectorEncontrado = true;
                        break;
                    }
                }

                // Si la fecha no existe, agregarla al array `totalInspeccionesContadas`
                if (!$inspectorEncontrado) {
                    $registro['datos']['totalInspecciones'][] = $inspeccionData;
                }

                $inspectorEncontrado = true;

                break;
            }
        }

        // Si el inspector no fue encontrado, agregar un nuevo registro con el array de fechas
        if (!$inspectorEncontrado) {
            $nuevoRegistro = [
                'datos' => [
                    'cc_inspector' => $ccInspector,
                    'totalInspecciones' => [$inspeccionData], // Nuevo array con la primera inspección
                ]
            ];
            // Agregar el nuevo registro al array de `noDoblesFestivos`
            $corteHisJson['doblesSabados'][] = $nuevoRegistro;
        }

        $sqlProHis->dobles_sabados = json_encode($corteHisJson);
        $sqlProHis->save();
        return response()->json(['success' => true]);
    }

    public function noContarDoblesSaturday(Request $request)
    {

        $corte = session('corteEnviar');
        $fecha = $request->input('fecha');
        $inspector = $request->input('ccInspector');

        $sqlProHis = TblProduccionHistorico::where('id_corte', $corte['id'])->first();

        $corteHisJson = json_decode($sqlProHis->dobles_sabados, true);

        if ($corteHisJson != null) {
            foreach ($corteHisJson['doblesSabados'] as &$datos) {
                if ($inspector == $datos['datos']['cc_inspector']) {
                    foreach ($datos['datos']['totalInspecciones'] as $key => $fechaExistente) {
                        if ($fecha == $fechaExistente['fecha']) {
                            unset($datos['datos']['totalInspecciones'][$key]);
                        }
                    }
                    // Reindexamos el array de fechas para evitar índices huecos
                    $datos['datos']['totalInspecciones'] = array_values($datos['datos']['totalInspecciones']);
                }
            }
        }

        $sqlProHis->dobles_sabados = json_encode($corteHisJson);
        $sqlProHis->save();
        return response()->json(['success' => true]);
    }
}
