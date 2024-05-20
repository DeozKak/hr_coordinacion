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
        $fechaActual = date('Y-m-d');
        $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fechaActual)
            ->where('fecha_fin', '>=', $fechaActual)
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
        $fechaActual = date('Y-m-d');

        $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fechaActual)
            ->where('fecha_fin', '>=', $fechaActual)
            ->first();

        $diasIntermedios = $this->DiasIntermedios($corte);

        // sacar inspectores
        $inspectores = tbl_insp_cali::orderBy('apellidos', 'asc')->get();

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

            // Generar una clave única para la caché basada en las fechas del corte
            $cacheKey = 'dias_festivos_' . $fechaInicio->format('Ymd') . '_' . $fechaFin->format('Ymd');

            // Verificar si los días festivos ya están en caché
            $diasFestivos = Cache::get($cacheKey);

            if (!$diasFestivos) {
                $diasFestivos = [];

                // Calcular y almacenar los días festivos
                for ($date = $fechaInicio; $date->lte($fechaFin); $date->addDay()) {
                    $fechas[$date->format('Y-m-d')] = ""; // Inicializa todas las fechas con 0 contratos
                    if (CalendarioColombia::date($date->format('Y-m-d'))->isHoliday()) {
                        $diasFestivos[] = $date->format('Y-m-d');
                    }
                }

                // Guardar los días festivos en caché por un tiempo determinado (por ejemplo, 24 horas)
                Cache::put($cacheKey, $diasFestivos, $duracionCorte);
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


            // contadores dobles contratos
            foreach ($contratosPorDia as $contrato) {

                foreach ($diasFestivos as $festivo) {

                    if ($festivo == $contrato->fecha) {
                        $contadorFestivos += $contrato->total_contratos;
                    }
                }

                /*   if($contrato->fecha == '2024-04-25'){
                    dd($contrato);
                } */
                $fechas[$contrato->fecha] = $contrato->total_contratos;
                $sumaInspecciones += $contrato->total_contratos;
            }

            $sabadosDobles = $this->calcularDobles($referenciaInicio, $referenciaFin, $inspector, $diasFestivos);

            foreach ($contratosPorCategoria as $contrato_C) {

                if ($contrato_C->CATEGORIA === 'COMERCIAL') {
                    $contadorComerciales = $contrato_C->total_contratos;
                }
            }

            foreach ($contratosPorRecinto as $contrato_R) {
                if ($contrato_R->{'4_RECINTOS'} === 'SI') {
                    $contratos4recintos = $contrato_R->total_contratos;
                }
            }




            // Crear el array final para el inspector
            $datosInspector = [
                'cedula' => $inspector->cedula,
                'nombres' => $inspector->apellidos . ' ' . $inspector->nombres,
            ];

            // Agregar los contratos por fecha
            foreach ($fechas as $fecha => $total_contratos) {
                $datosInspector[$fecha] = $total_contratos;
            }

            $datosInspector['total'] = $sumaInspecciones;
            $datosInspector['matrices'] = $contadorMatrices;
            $datosInspector['festivos'] = $contadorFestivos + $sabadosDobles;
            $datosInspector['diseños_especiales'] = null;
            $datosInspector['4_recintos'] = $contratos4recintos;
            $datosInspector['comerciales'] = $contadorComerciales;

            if ($sumaInspecciones === 0 && $inspector->state === 0) {
                continue;
            }
            $produccionInspector[] = $datosInspector;
        }


        // Calcula la diferencia en milisegundos

        $reponse = [
            'diasIntermedios' => $diasIntermedios,
            'produccionInspector' => $produccionInspector
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

        foreach ($semanas as $index => $semana) {
            // Obteniendo contratos de lunes a viernes
            $contratosPorSemana = tbl_bitacora_contrato::whereBetween('FECHA', [$semana['inicio'], $semana['fin']])
                ->whereRaw('DAYOFWEEK(FECHA) between 2 and 6') // Filtrando días de lunes (2) a viernes (6)
                ->selectRaw('DATE(FECHA) as fecha, COUNT(*) as total_contratos')
                ->groupBy('fecha')
                ->where('CC_OPERARIO', '=', $inspector->cedula)
                ->get();

            $totalContratos = 0;

            // Verificar cada contrato por día y excluir días festivos
            foreach ($contratosPorSemana as $contrato) {
                // Verificar si el día es festivo
                $esFestivo = in_array($contrato->fecha, $diasFestivos);

                if (!$esFestivo) {
                    // Sumar solo si no es un día festivo
                    $totalContratos += $contrato->total_contratos;
                }
            }
            if ($totalContratos >= 48) {
                // Obtener los contratos del sábado de esa semana
                $contratosSabado = tbl_bitacora_contrato::whereBetween('FECHA', [$semana['inicio'], $semana['fin']])
                    ->whereRaw('DAYOFWEEK(FECHA) = 7') // Filtrando el día sábado (7)
                    ->selectRaw('DATE(FECHA) as fecha, COUNT(*) as total_contratos')
                    ->groupBy('fecha')
                    ->where('CC_OPERARIO', '=', $inspector->cedula)
                    ->get();
                if ($contratosSabado->sum('total_contratos') === 0){
                    $totalContratosSabado = null;
                }
                // Sumar los contratos del sábado
                $totalContratosSabado = $contratosSabado->sum('total_contratos');

                return $totalContratosSabado;
            }
            // Guardar el total de contratos en el array de semanas
            $semanas[$index]['contratos'] = $totalContratos;

            // Descomentar para depuración

        }


        return null;
    }
}
