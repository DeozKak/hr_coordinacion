<?php

namespace App\Http\Controllers;

use App\Models\tbl_bitacora_archivo;
use App\Models\tbl_localidades_municipio;
use App\Models\tbl_produccion_zona;
use App\Models\tbl_insp_cali;
use App\Models\tbl_bitacora_contrato;
use App\Models\tbl_produccion_corte;
use App\Models\tbl_produccion_historico;
use App\Models\User;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Rmunate\Calendario\CalendarioColombia;
use App\Notifications\Produccion;


class ProduccionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->id) {
            $corte = tbl_produccion_corte::find($request->id);
        } else {
            $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-MM-DD'
            $fecha_resta_un_dia = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));
            $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
                ->where('fecha_fin', '>=', $fecha_resta_un_dia)
                ->first();
        }
        $warning = null;
        $error = false;
        // sacar cortes activos

        if ($corte === null && !$error) {
            $error = true;
            $warning = 'No hay corte activo';
            return view('produccion.index', ['produccionInspector' => "produccionInspector", 'contratosCategoria' => "contratosCategoria", 'conteoContratosPorZona' => " conteoContratosPorZona", 'corte' => $corte, 'warning' => $warning]);
        }
        // sacar contratos del corte activo
        $contratosCorte = tbl_bitacora_contrato::where('FECHA', '>=', $corte->fecha_inicio)
            ->where('FECHA', '<=', $corte->fecha_fin)->where('state', '=', 1)
            ->get();

        if (count($contratosCorte->toArray()) === 0 && !$error) {
            $error = true;
            $warning = 'No hay contratos en el corte activo';
        }
        // sacar inspectores
        $inpectores = tbl_insp_cali::all();

        if (count($inpectores->toArray()) === 0 && !$error) {
            $error = true;
            $warning = 'No hay inspectores activos';
        }
        $contadortotal = 0;
        // sacar produccion de cada inspector
        $produccionInspector = array();
        foreach ($inpectores as $inspector) {

            $numerosContratos = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)->where('FECHA', '>=', $corte->fecha_inicio)
                ->where('FECHA', '<=', $corte->fecha_fin)->where('state', '=', 1)->where('TIPO_TRABAJO', '!=', 'FI-29 revisión periódica línea matriz')
                ->count();

            if ($numerosContratos === 0 && $inspector->state === 0) {
                continue;
            }elseif($numerosContratos === 0){
                continue;
            }
            $contadortotal += $numerosContratos;

            $produccionInspector[] =
                [
                    'nombres' => $inspector->apellidos,
                    'contratos' => $numerosContratos
                ];
        }
        // sacar categorias de los contratos
        $contratosCategoria = tbl_bitacora_contrato::select('CATEGORIA')->where('FECHA', '>=', $corte->fecha_inicio)
            ->where('FECHA', '<=', $corte->fecha_fin)->where('state', '=', 1)->where('TIPO_TRABAJO', '!=', 'FI-29 revisión periódica línea matriz')
            ->get();

        if (count($contratosCategoria->toArray()) === 0 && !$error) {
            $error = true;
            $warning = 'No hay categorias en los contratos';
        }
        // sacar cantidad de contratos por zona
        $zonas = tbl_produccion_zona::all();
        $conteoContratosPorZona = array();
        $municipiosNoEncontrados = array();

        foreach ($zonas as $zona) {
            $count = tbl_localidades_municipio::select('nombre')->where('id_zona', '=', $zona->id)->get();
            $contador = 0;
            // Verificador de municipios no encontrados
            // Obtener los municipios de tbl_bitacora_contrato
            $municipiosContratos = tbl_bitacora_contrato::select('MUNICIPIO')
                ->where('FECHA', '>=', $corte->fecha_inicio)
                ->where('FECHA', '<=', $corte->fecha_fin)
                ->where('state', '=', 1)
                ->where('TIPO_TRABAJO', '!=', 'FI-29 revisión periódica línea matriz')
                ->distinct()
                ->get()
                ->pluck('MUNICIPIO');

            // Obtener los municipios de tbl_localidades_municipios
            $municipiosLocalidades = tbl_localidades_municipio::select('nombre')
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
            foreach ($count as $c) {

                $cantidades = tbl_bitacora_contrato::where('MUNICIPIO', '=', $c->nombre)->where('FECHA', '>=', $corte->fecha_inicio)
                    ->where('FECHA', '<=', $corte->fecha_fin)->where('state', '=', 1)->where('TIPO_TRABAJO', '!=', 'FI-29 revisión periódica línea matriz')->count();
                $contador += $cantidades;
                // Consulta adicional para obtener los registros que no cumplen con la condición del municipio

            }

            $conteoContratosPorZona[] = [
                'zona' => $zona->nombre,
                'contratos' => $contador
            ];
        }
        if (count($conteoContratosPorZona) === 0 && !$error) {
            $error = true;
            $warning = 'error en las zonas';
        }


        if ($error) {
            return view('produccion.index', ['produccionInspector' => $produccionInspector, 'contratosCategoria' => $contratosCategoria, 'conteoContratosPorZona' => $conteoContratosPorZona, 'corte' => $corte, 'warning' => $warning]);
        }
        return view('produccion.index', compact('produccionInspector', 'contratosCategoria', 'conteoContratosPorZona', 'corte', 'warning', 'municipiosNoEncontrados'));
    }

    public function detallesCorte($id)
    {

        session(['id_corte' => $id]);

        return $this->detalles();
    }

    public function detalles()
    {

        $municipios = tbl_localidades_municipio::all();
        if (session('id_corte')) {
            $corte = tbl_produccion_corte::find(session('id_corte'));
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
        $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
            ->where('fecha_fin', '>=', $fecha_resta_un_dia)
            ->first();
        return view('produccion.detalles', compact('municipios'));
    }

    public function datosDetalles(Request $request)
    {

        if (session('id_corte') || $request->idCorteDetalles) {
            $idCorte = session('id_corte') ?? $request->idCorteDetalles;
            $corte = tbl_produccion_corte::find($idCorte);

            session()->put('corteEnviar', $corte);

            $exist = tbl_produccion_historico::where('id_corte', $corte->id)->exists();

            if ($exist) {
                $historico = tbl_produccion_historico::where('id_corte', $corte->id)->first();
                $response = json_decode($historico->data);
                return response()->json( $response);
            }
            session()->forget('id_corte');

        } else {
            // $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-MM-DD'
            $fecha_actual = "2024-10-20"; // Obtiene la fecha actual en formato 'YYYY-MM-DD'
            $fecha_resta_un_dia = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));

            $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
                ->where('fecha_fin', '>=', $fecha_resta_un_dia)
                ->first();
            session()->put('corteEnviar', $corte);
        }
        $diasIntermedios = $this->DiasIntermedios($corte);
        if ($diasIntermedios == null) {
            return response()->json(['error' => 'No hay corte activo']);
        }
        $cantidad_dias = count($diasIntermedios);

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
        $inspectores = tbl_insp_cali::orderBy('apellidos', 'asc')->get();
        $sabados = array();
        // sacar produccion de cada inspector
        $produccionInspector = array();
        foreach ($inspectores as $inspector) {
            //inicializar variables contadores
            $contadorDiseñosEspeciales = null;
            $contadorMatrices = null;
            $contadorFestivos = null;
            $contratos4recintos = null;
            $contadorComerciales = null;
            $sumaInspecciones = 0;
            // Generar todas las fechas en el rango
            $fechaInicio = Carbon::parse($corte->fecha_inicio);
            $fechaFin = Carbon::parse($corte->fecha_fin);
            $fechas = [];

            $referenciaInicio = $fechaInicio->copy();
            $referenciaFin = $fechaFin->copy();



            // Calcular la duración del corte en minutos
            $duracionCorte = $fechaInicio->diffInMinutes($fechaFin);

            // Calcular la fecha de inicio y fin del rango de 1 mes antes y 1 mes después
            $fechaInicioRango = $fechaInicio->copy()->subMonth();
            $fechaFinRango = $fechaInicio->copy()->addMonth();

            // Generar una clave única para la caché basada en las fechas del rango
            $cacheKeyRango = 'dias_festivos_rango_' . $fechaInicioRango->format('Ymd') . '_' . $fechaFinRango->format('Ymd');

            // Verificar si los días festivos en el rango ya están en caché
            $diasFestivosRango = Cache::get($cacheKeyRango);
            /* Cache::forget($cacheKeyRango); */

            if (!$diasFestivosRango) {
                $diasFestivosRango = [];

                // Calcular y almacenar los días festivos en el rango de fechas
                for ($date = $fechaInicioRango; $date->lte($fechaFinRango); $date->addDay()) {
                    $fechas[$date->format('Y-m-d')] = "";
                    // Filtrar las fechas para mantener solo las originales
                    $fechas = array_filter($fechas, function ($fecha) use ($fechaInicio, $fechaFin) {
                        return $fecha >= $fechaInicio->format('Y-m-d') && $fecha <= $fechaFin->format('Y-m-d');
                    }, ARRAY_FILTER_USE_KEY);
                    if (CalendarioColombia::date($date->format('Y-m-d'))->isHoliday()) {
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


            // Realizar la consulta
            $contratosPorDia = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->where('FECHA', '>=', $corte->fecha_inicio)
                ->where('FECHA', '<=', $corte->fecha_fin)
                ->where('state', '=', 1)
                ->where('TIPO_TRABAJO', '!=', 'FI-29 revisión periódica línea matriz')
                ->select(DB::raw('DATE(FECHA) as fecha, COUNT(*) as total_contratos'))
                ->groupBy('fecha')
                ->get();
                if ($contratosPorDia->sum('total_contratos') == 0) {
                    continue;
                }
            $contratosPorCategoria = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->where('state', '=', 1)
                ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
                ->select('CATEGORIA', DB::raw('COUNT(*) as total_contratos'))
                ->groupBy('CATEGORIA')
                ->get();

            $contratosPorRecinto = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->where('state', '=', 1)
                ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
                ->select('4_RECINTOS', DB::raw('COUNT(*) as total_contratos'))
                ->groupBy('4_RECINTOS')
                ->get();

            $matrices = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->where('state', '=', 1)
                ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
                ->where('TIPO_TRABAJO', '=', 'FI-29 revisión periódica línea matriz')
                ->count();

            $diseñosEspeciales = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->where('state', '=', 1)
                ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
                ->where('diseno_especial', '=', 1)
                ->count();
            if ($diseñosEspeciales > 0) {
                $contadorDiseñosEspeciales = $diseñosEspeciales;
            }

            if ($matrices > 0) {
                $contadorMatrices = $matrices;
            }
            // contadores dobles contratos

            foreach ($contratosPorDia as $contrato) {

                foreach ($diasFestivosRango as $festivo) {

                    if ($festivo == $contrato->fecha) {
                        $contadorFestivos += $contrato->total_contratos;
                    }
                }

                $fechas[$contrato->fecha] = $contrato->total_contratos;

                $sumaInspecciones += $contrato->total_contratos;
            }

            $sabadosDobles = $this->calcularDobles($referenciaInicio, $referenciaFin, $inspector, $diasFestivosRango, $corte->dobles);

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

            $datosInspector['festivos'] = $totalFestivos;
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
            $datosInspector['dias_laborados'] = $contadorDiasLaborados;
            $datosInspector['promedio'] = number_format($datosInspector['total'] / $cantidad_dias, 1);
            $datosInspector['meta'] = $corte->meta;
            $datosInspector['porcentaje_meta'] = '%' . number_format(($datosInspector['sub_total'] / $datosInspector['meta']) * 100, 2);

            $produccionInspector[] = $datosInspector;
        }

        $sqlHistorico = tbl_produccion_historico::where('id_corte', $corte->id)->first();

        $jsonNoDobles = json_decode($sqlHistorico->no_dobles, true);

        if($jsonNoDobles != null){
            foreach($sabados as $sabadoIndex => &$sabado) {
                foreach($sabado['datos'] as $datoIndex => $dato) {
                    foreach($jsonNoDobles as $datos) {
                        foreach($datos as $item) {
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
        ];

        $exist = tbl_produccion_historico::where('id_corte', $corte->id)->exists();

        if ($exist) {
            $historico = tbl_produccion_historico::where('id_corte', $corte->id)->first();
            $historico->data = json_encode($reponse);
            $historico->save();
        }else{
            $historico = new tbl_produccion_historico();
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

    public function calcularDobles($fechaInicio, $fechaFin, $inspector, $diasFestivos, $valDobles)
    {
        // Inicializar variables para contadores
        $contadorDiasSabados = null;
        $sabadosdobles = array();

        // Generar las semanas en el rango del corte
        $semanas = [];
        for ($date = $fechaInicio->copy(); $date->lte($fechaFin); $date->addWeek()) {
            $inicioSemana = $date->copy()->startOfWeek();
            $finSemana = $date->copy()->endOfWeek();

            // Ajustar la fecha de fin de semana si excede la fecha final
            if ($finSemana->gt($fechaFin)) {
                $finSemana = $fechaFin->copy(); // Limitar al último día del rango
            }

            $semanas[] = [
                'inicio' => $inicioSemana->format('Y-m-d'),
                'fin' => $finSemana->format('Y-m-d'),
                'contratos' => 0,
                'festivos' => 0,
            ];
        }

        $registro = array();
        foreach ($semanas as $index => $semana) {
            // Inicializar el total de contratos
            $totalContratos = 0;

            // Verificar si hay días festivos en la semana
            $hayFestivoEnSemana = false;
            foreach ($diasFestivos as $festivo) {
                $diaSemana = date('N', strtotime($festivo)); // Obtener el número del día de la semana (1 para lunes, 7 para domingo)

                if ($diaSemana >= 1 && $diaSemana <= 5) { // Verificar si el día festivo cae en un día de la semana (de lunes a viernes)
                    if ($festivo >= $semana['inicio'] && $festivo <= $semana['fin']) {
                        $hayFestivoEnSemana = true;
                        break;
                    }
                }
            }

            // Obtener contratos de lunes a viernes
            $contratosPorSemana = tbl_bitacora_contrato::whereBetween('FECHA', [$semana['inicio'], $semana['fin']])
                ->where('state', '=', 1)
                ->whereRaw('DAYOFWEEK(FECHA) between 2 and 6') // Filtrando días de lunes (2) a viernes (6)
                ->selectRaw('DATE(FECHA) as fecha, COUNT(*) as total_contratos')
                ->groupBy('fecha')
                ->where('CC_OPERARIO', '=', $inspector->cedula)
                ->get();


            // Obtener contratos del sábado
            $contratosSabado = tbl_bitacora_contrato::whereBetween('FECHA', [$semana['inicio'], $semana['fin']])
                ->where('state', '=', 1)
                ->whereRaw('DAYOFWEEK(FECHA) = 7') // Filtrando el día sábado (7)
                ->selectRaw('DATE(FECHA) as fecha, COUNT(*) as total_contratos')
                ->groupBy('fecha')
                ->where('CC_OPERARIO', '=', $inspector->cedula)
                ->get();


            // Verificar cada contrato por día y excluir días festivos
            foreach ($contratosPorSemana as $contrato) {
               /*  $esFestivo = in_array($contrato->fecha, $diasFestivos);
                if (!$esFestivo) { */
                    $totalContratos += $contrato->total_contratos;
               /*  } */
            }

            // Ajustar los límites de las condicionales si hay un festivo en la semana
            $limiteContratos = $hayFestivoEnSemana ? $valDobles - 10 : $valDobles;
            $limiteContratosBajo = $hayFestivoEnSemana ? $valDobles - 12 : $valDobles - 2;
            $limiteContratosMedio = $hayFestivoEnSemana ? $valDobles - 11 : $valDobles - 1;
            if ($contratosSabado->count() > 0) {

                $fechaSabado = $contratosSabado->first()->fecha;
                $esSabadoFestivo = in_array($fechaSabado, $diasFestivos);

                if (!$esSabadoFestivo) {

                    if ($totalContratos >= $limiteContratos) {

                        // Sumar los contratos del sábado
                        $totalContratosSabado = $contratosSabado->sum('total_contratos');
                        $contadorDiasSabados += $totalContratosSabado;
                        try {
                            $sabadosdobles[] = [
                                'fecha' => $contratosSabado->first()->fecha,
                                'cc_inspector' => $inspector->cedula
                            ];
                        } catch (\Exception $e) {
                        }
                    } elseif ($totalContratos < $limiteContratos && $totalContratos >= $limiteContratosBajo) {

                        $corte = session('corteEnviar');
                        $sqlHistorico = tbl_produccion_historico::where('id_corte', $corte['id'])->first();
                        $jsonNoDobles = json_decode($sqlHistorico->no_dobles, true);

                        if($jsonNoDobles != null){
                            foreach($jsonNoDobles as $datos) {
                                foreach($datos as $item) {
                                    if($inspector->cedula == $item['datos']['cc_inspector'] && in_array($contratosSabado->first()->fecha,$item['datos']['fechas'])){
                                        continue 3;
                                    }
                                }
                            }
                        }

                        // Sumar los contratos del sábado con ajustes
                        $totalContratosSabado = $contratosSabado->sum('total_contratos');
                        $totalContratosSabado = ($totalContratos === $limiteContratosMedio) ? $totalContratosSabado - 1 : $totalContratosSabado - 2;

                        // Validación para evitar contar valores cero o negativos
                        if ($totalContratosSabado > 0) {
                            $contadorDiasSabados += $totalContratosSabado;
                        }

                        // Validación para el array de sábados dobles
                        if ($totalContratosSabado > 0 && $contratosSabado->isNotEmpty()) { // <-- Nueva condición
                            try {
                                $sabadosdobles[] = [
                                    'fecha' => $contratosSabado->first()->fecha,
                                    'cc_inspector' => $inspector->cedula
                                ];
                            } catch (\Exception $e) {
                                // Manejo de excepciones (opcional)
                            }
                        }
                    }
                } else {
                }
            }
            // Guardar el total de contratos en el array de semanas
            $semanas[$index]['contratos'] = $totalContratos;

            // Descomentar para depuración
            /*  if ($inspector->cedula === '7691266') {
                dd($totalContratosSabado);
            } */
        }
        // Descomentar para ver el resultado final
        // dd($contadorDiasSabados);

        return [
            'contadorDiasSabados' => $contadorDiasSabados,
            'sabadosdobles' => $sabadosdobles
        ];
    }

    public function detallesDiario($fecha, $inspector)
    {
        $corte = session('corteEnviar');

        $contratosDia = tbl_bitacora_contrato::selectRaw("tbl_bitacora_contratos.id, CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS nombre_completo, tbl_bitacora_contratos.CC_OPERARIO, tbl_bitacora_contratos.MUNICIPIO, tbl_bitacora_contratos.FECHA, tbl_bitacora_contratos.No_ACTA, tbl_bitacora_contratos.TIPO_TRABAJO, tbl_bitacora_contratos.CONTRATO, tbl_bitacora_contratos.ORDEN_TRABAJO, tbl_bitacora_contratos.ORDEN_EXT, tbl_bitacora_contratos.CATEGORIA, tbl_bitacora_contratos.RESULTADO_CIERRE, tbl_bitacora_contratos.HORA_INICIO, tbl_bitacora_contratos.HORA_FINAL, tbl_bitacora_contratos.DURACION_INSP,
        tbl_bitacora_contratos.`4_RECINTOS`,tbl_bitacora_contratos.state,tbl_bitacora_contratos.diseno_especial,tbl_bitacora_contratos.vence")
            ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_contratos.CC_OPERARIO')
            ->where('tbl_bitacora_contratos.CC_OPERARIO', '=', $inspector)
            ->where('tbl_bitacora_contratos.FECHA', '=', $fecha)
            ->get();


        // Consultar la tabla `tbl_produccion_historico` usando el id del corte
        $sqlProHis = tbl_produccion_historico::where('id_corte', $corte['id'])->first();

        $jsonNoDobles = json_decode($sqlProHis->no_dobles,true);

        $flag = false;
        if($jsonNoDobles != null){
            foreach($jsonNoDobles as $datos) {
                foreach($datos as $item) {
                    if($inspector == $item['datos']['cc_inspector'] && in_array($fecha, $item['datos']['fechas'])){
                        $flag = true;
                    }
                }
            }
        }

        return response()->json([
            $contratosDia,
            $flag,
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
            $contrato = tbl_bitacora_contrato::find($id);
            $contrato->{$datos['prop']} = $datos['newValue'];
            $contrato->save();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el contrato']);
        }
        return response()->json(['message' => 'OK']);
    }

    public function eliminarDetallesDiario($id)
    {

        $contrato = tbl_bitacora_contrato::find($id);
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
        $contrato = tbl_bitacora_contrato::find($id);

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
        $contrato = new tbl_bitacora_contrato();
        $nomInspector = tbl_insp_cali::select('apellidos', 'nombres')->where('cedula', '=', $ccOperario)->first();
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
        $usuarios = User::role(['admin', 'Director', 'Residente'])->get();
        $usuarioLog = Auth::user();

        // Enviar la notificación a cada usuario
        foreach ($usuarios as $usuario) {
            $usuario->notify(new Produccion($request->data[7], $usuarioLog->name, $fechaDB, $nomInspector));
        }
        return response()->json(['ok' => 'Insertado correctamente.']);
    }

    public function consultarBitacora($fecha, $ccOperario)
    {

        // Consultar la tabla tbl_insp_cali para obtener el nombre del supervisor
        $inspector = tbl_insp_cali::select('users.name AS supervisor')
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
        $bitacoras = tbl_bitacora_archivo::select('id', 'nombre_archivo')->get();

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

    public function zonas(Request $request)
    {
        if (session('id_corte') || $request->idCorteDetalles) {
            $idCorte = session('id_corte') ?? $request->idCorteDetalles;
            $corte = tbl_produccion_corte::find($idCorte);
            session()->forget('id_corte');
            session()->save();
        } else {
            $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-MM-DD'
            $fecha_resta_un_dia = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));

            $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
                ->where('fecha_fin', '>=', $fecha_resta_un_dia)
                ->first();
        }
        if ($corte == null) {
            return response()->json(['error' => 'No hay corte activo']);
        }
        $diasIntermedios = $this->DiasIntermedios($corte);

        $zonas = tbl_produccion_zona::select('id', 'nombre')->get();

        foreach ($zonas as $zona) {
            $ContratosPorZonaReidencial = ['zona' => $zona->nombre . " RESIDENCIAL"];
            $ContratosPorZonaComercial = ['zona' => $zona->nombre . " COMERCIAL"];

            $period = new DatePeriod(
                new DateTime($corte->fecha_inicio),
                new DateInterval('P1D'),
                (new DateTime($corte->fecha_fin))->modify('+1 day')
            );

            foreach ($period as $date) {
                $fecha = $date->format('Y-m-d');

                // Subconsulta para obtener los contratos residenciales
                $contratosResidenciales = DB::table('tbl_bitacora_contratos')
                    ->select(DB::raw('count(*) as total'))
                    ->where('CATEGORIA', 'RESIDENCIAL')
                    ->where('FECHA', $fecha)
                    ->where('state', 1)
                    ->where('TIPO_TRABAJO', '!=', 'FI-29 revisión periódica línea matriz')
                    ->whereIn('MUNICIPIO', function ($query) use ($zona) {
                        $query->select('nombre')
                            ->from('tbl_localidades_municipios')
                            ->where('id_zona', $zona->id);
                    })
                    ->first();

                // Subconsulta para obtener los contratos comerciales
                $contratosComerciales = DB::table('tbl_bitacora_contratos')
                    ->select(DB::raw('count(*) as total'))
                    ->where('CATEGORIA', 'COMERCIAL')
                    ->where('FECHA', $fecha)
                    ->where('state', 1)
                    ->where('TIPO_TRABAJO', '!=', 'FI-29 revisión periódica línea matriz')
                    ->whereIn('MUNICIPIO', function ($query) use ($zona) {
                        $query->select('nombre')
                            ->from('tbl_localidades_municipios')
                            ->where('id_zona', $zona->id);
                    })
                    ->first();

                $ContratosPorZonaReidencial[$fecha] = $contratosResidenciales->total;
                $ContratosPorZonaComercial[$fecha] = $contratosComerciales->total;
            }

            $conteoContratosResidencial[] = $ContratosPorZonaReidencial;
            $conteoContratosComercial[] = $ContratosPorZonaComercial;
        }

        $response = [
            'diasIntermedios' => $diasIntermedios,
            'residencial' => $conteoContratosResidencial,
            'comercial' => $conteoContratosComercial
        ];

        // Retornar la respuesta JSON
        return response()->json($response);
    }

    public function guardarNoDobles(Request $request) {
        // Obtener datos desde la sesión y la solicitud
        $corte = session('corteEnviar');
        $fecha = $request->input('fecha');
        $inspector = $request->input('ccInspector');

        // Consultar la tabla `tbl_produccion_historico` usando el id del corte
        $sqlProHis = tbl_produccion_historico::where('id_corte', $corte['id'])->first();

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

    public function contarDobles(Request $request){
        $corte = session('corteEnviar');
        $fecha = $request->input('fecha');
        $inspector = $request->input('ccInspector');

        $sqlProHis = tbl_produccion_historico::where('id_corte', $corte['id'])->first();

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
}
