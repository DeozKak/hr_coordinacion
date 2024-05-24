<?php

namespace App\Http\Controllers;

use App\Models\tbl_bitacora_archivo;
use App\Models\tbl_localidades_municipio;
use App\Models\tbl_produccion_zona;
use App\Models\tbl_insp_cali;
use App\Models\tbl_bitacora_contrato;
use App\Models\tbl_produccion_corte;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Rmunate\Calendario\CalendarioColombia;

use function Laravel\Prompts\warning;


class ProduccionController extends Controller
{
    public function index()
    {
        $warning = null;
        $error = false;
        // sacar cortes activos
        $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-MM-DD'
        $fecha_resta_un_dia = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));
        $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
            ->where('fecha_fin', '>=', $fecha_resta_un_dia)
            ->first();

        if (count($corte->toArray()) === 0 && !$error) {
            $error = true;
            $warning = 'No hay corte activo';
        }
        // sacar contratos del corte activo
        $contratosCorte = tbl_bitacora_contrato::where('FECHA', '>=', $corte->fecha_inicio)
            ->where('FECHA', '<=', $corte->fecha_fin)
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
        // sacar produccion de cada inspector
        $produccionInspector = array();
        foreach ($inpectores as $inspector) {

            $numerosContratos = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)->where('FECHA', '>=', $corte->fecha_inicio)
                ->where('FECHA', '<=', $corte->fecha_fin)
                ->count();
            if ($numerosContratos === 0 && $inspector->state === 0) {
                continue;
            }
            $produccionInspector[] =
                [
                    'nombres' => $inspector->apellidos,
                    'contratos' => $numerosContratos
                ];
        }
        // sacar categorias de los contratos
        $contratosCategoria = tbl_bitacora_contrato::select('CATEGORIA')->where('FECHA', '>=', $corte->fecha_inicio)
            ->where('FECHA', '<=', $corte->fecha_fin)
            ->get();

        if (count($contratosCategoria->toArray()) === 0 && !$error) {
            $error = true;
            $warning = 'No hay categorias en los contratos';
        }
        // sacar cantidad de contratos por zona
        $zonas = tbl_produccion_zona::all();
        $conteoContratosPorZona = array();

        foreach ($zonas as $zona) {
            $count = tbl_localidades_municipio::select('nombre')->where('id_zona', '=', $zona->id)->get();
            $contador = 0;
            foreach ($count as $c) {

                $cantidades = tbl_bitacora_contrato::where('MUNICIPIO', '=', $c->nombre)->where('FECHA', '>=', $corte->fecha_inicio)
                    ->where('FECHA', '<=', $corte->fecha_fin)->count();
                $contador += $cantidades;
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
        return view('produccion.index', compact('produccionInspector', 'contratosCategoria', 'conteoContratosPorZona', 'corte', 'warning'));
    }

    public function detalles()
    {


        return view('produccion.detalles');
    }

    public function datosDetalles()
    {
        $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-MM-DD'
        $fecha_resta_un_dia = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));


        $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
            ->where('fecha_fin', '>=', $fecha_resta_un_dia)
            ->first();

        $diasIntermedios = $this->DiasIntermedios($corte);
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

            if (!$diasFestivosRango) {
                $diasFestivosRango = [];

                // Calcular y almacenar los días festivos en el rango de fechas
                for ($date = $fechaInicioRango; $date->lte($fechaFinRango); $date->addDay()) {
                    $fechas[$date->format('Y-m-d')] = "";
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
                ->select(DB::raw('DATE(FECHA) as fecha, COUNT(*) as total_contratos'))
                ->groupBy('fecha')
                ->get();


            $contratosPorCategoria = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
                ->select('CATEGORIA', DB::raw('COUNT(*) as total_contratos'))
                ->groupBy('CATEGORIA')
                ->get();

            $contratosPorRecinto = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
                ->select('4_RECINTOS', DB::raw('COUNT(*) as total_contratos'))
                ->groupBy('4_RECINTOS')
                ->get();

            $matrices = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
                ->where('TIPO_TRABAJO', '=', 'FI-29 revisión periódica línea matriz')
                ->count();


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

            $sabadosDobles = $this->calcularDobles($referenciaInicio, $referenciaFin, $inspector, $diasFestivosRango);

            foreach ($contratosPorCategoria as $contrato_C) {

                if ($contrato_C->CATEGORIA === 'COMERCIAL') {
                    $contadorComerciales = $contrato_C->total_contratos;
                }
            }

            foreach ($contratosPorRecinto as $contrato_R) {

                if ($contrato_R->{'4_RECINTOS'} != 'NO') {
                    $contratos4recintos = $contratos4recintos + $contrato_R->{'4_RECINTOS'};
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
            $datosInspector['diseños_especiales'] = null;
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
            $datosInspector['meta'] = 180;
            $datosInspector['porcentaje_meta'] = '%' . number_format(($datosInspector['sub_total'] / $datosInspector['meta']) * 100, 2);





            $produccionInspector[] = $datosInspector;
        }


        // Calcula la diferencia en milisegundos

        $reponse = [
            'diasIntermedios' => $diasIntermedios,
            'produccionInspector' => $produccionInspector,
            'diasFestivos' => $diasFestivosRango,
            'sabadodobles' => $sabados,
            'fechasIntermedias' => $fechasIntermedias,

        ];

        return response()->json($reponse);
    }

    public function DiasIntermedios($corte)
    {
        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $fechaActual = date('Y-m-d');

        $fechaInicio = new DateTime($corte->fecha_inicio);
        $fechaFin = new DateTime($corte->fecha_fin);
        $fechaFin->modify('+1 day');

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


    public function calcularDobles($fechaInicio, $fechaFin, $inspector, $diasFestivos)
    {
        // Inicializar variables para contadores
        $contadorDiasSabados = null;
        $sabadosdobles = array();


        // Generar las semanas en el rango del corte
        $semanas = [];
        for ($date = $fechaInicio->copy(); $date->lte($fechaFin); $date->addWeek()) {
            $inicioSemana = $date->copy()->startOfWeek();
            $finSemana = $date->copy()->endOfWeek();
            if ($inicioSemana->lte($fechaFin)) {
                $semanas[] = [
                    'inicio' => $inicioSemana->format('Y-m-d'),
                    'fin' => $finSemana->format('Y-m-d'),
                    'contratos' => 0,
                    'festivos' => 0,
                ];
            }
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
                ->whereRaw('DAYOFWEEK(FECHA) between 2 and 6') // Filtrando días de lunes (2) a viernes (6)
                ->selectRaw('DATE(FECHA) as fecha, COUNT(*) as total_contratos')
                ->groupBy('fecha')
                ->where('CC_OPERARIO', '=', $inspector->cedula)
                ->get();


            // Obtener contratos del sábado
            $contratosSabado = tbl_bitacora_contrato::whereBetween('FECHA', [$semana['inicio'], $semana['fin']])
                ->whereRaw('DAYOFWEEK(FECHA) = 7') // Filtrando el día sábado (7)
                ->selectRaw('DATE(FECHA) as fecha, COUNT(*) as total_contratos')
                ->groupBy('fecha')
                ->where('CC_OPERARIO', '=', $inspector->cedula)
                ->get();

            // Verificar cada contrato por día y excluir días festivos
            foreach ($contratosPorSemana as $contrato) {
                $esFestivo = in_array($contrato->fecha, $diasFestivos);

                if (!$esFestivo) {
                    $totalContratos += $contrato->total_contratos;
                }
            }

            // Ajustar los límites de las condicionales si hay un festivo en la semana
            $limiteContratos = $hayFestivoEnSemana ? 40 : 48;
            $limiteContratosBajo = $hayFestivoEnSemana ? 38 : 46;
            $limiteContratosMedio = $hayFestivoEnSemana ? 39 : 47;
            if ($contratosSabado->count() > 0) {


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

                    // Sumar los contratos del sábado con ajustes
                    $totalContratosSabado = $contratosSabado->sum('total_contratos');
                    $totalContratosSabado = ($totalContratos === $limiteContratosMedio) ? $totalContratosSabado - 1 : $totalContratosSabado - 2;
                    $contadorDiasSabados += $totalContratosSabado;
                    try {
                        $sabadosdobles[] = [
                            'fecha' => $contratosSabado->first()->fecha,
                            'cc_inspector' => $inspector->cedula
                        ];
                    } catch (\Exception $e) {
                    }
                }
            }
            // Guardar el total de contratos en el array de semanas
            $semanas[$index]['contratos'] = $totalContratos;

            // Descomentar para depuración
            // dd($contratosPorSemana, $contratosSabado, $totalContratos, $contadorDiasSabados);
        }
        // Descomentar para ver el resultado final
        return [
            'contadorDiasSabados' => $contadorDiasSabados,
            'sabadosdobles' => $sabadosdobles
        ];
    }


    public function detallesDiario($fecha, $inspector)
    {

        $contratosDia = tbl_bitacora_contrato::selectRaw("CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS nombre_completo, tbl_bitacora_contratos.CC_OPERARIO, tbl_bitacora_contratos.MUNICIPIO, tbl_bitacora_contratos.FECHA, tbl_bitacora_contratos.No_ACTA, tbl_bitacora_contratos.TIPO_TRABAJO, tbl_bitacora_contratos.CONTRATO, tbl_bitacora_contratos.ORDEN_TRABAJO, tbl_bitacora_contratos.ORDEN_EXT, tbl_bitacora_contratos.CATEGORIA, tbl_bitacora_contratos.RESULTADO_CIERRE, tbl_bitacora_contratos.HORA_INICIO, tbl_bitacora_contratos.HORA_FINAL, tbl_bitacora_contratos.DURACION_INSP, tbl_bitacora_contratos.`4_RECINTOS`")
            ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_contratos.CC_OPERARIO')
            ->where('tbl_bitacora_contratos.CC_OPERARIO', '=', $inspector)
            ->where('tbl_bitacora_contratos.FECHA', '=', $fecha)
            ->get();

        return response()->json($contratosDia);
    }
}
