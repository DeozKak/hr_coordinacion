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
use Illuminate\Http\Request;
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
        $tiempoInicio = microtime(true);
        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $fechaActual = date('Y-m-d');
        $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fechaActual)
            ->where('fecha_fin', '>=', $fechaActual)
            ->first();

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
        
         // sacar inspectores
         $inspectores = tbl_insp_cali::orderBy('apellidos', 'asc')->get();

      

         // sacar produccion de cada inspector
        $produccionInspector = array();
        foreach ($inspectores as $inspector) {
            //inicializar variables contadores
            $contadorMatrices = null;
            $contadorFestivos= null;
            $sumaInspecciones = 0;
            // Generar todas las fechas en el rango
            $fechaInicio = Carbon::parse($corte->fecha_inicio);
            $fechaFin = Carbon::parse($corte->fecha_fin);
            $fechas = [];
        
            for ($date = $fechaInicio; $date->lte($fechaFin); $date->addDay()) {
                $fechas[$date->format('Y-m-d')] = ""; // Inicializa todas las fechas con 0 contratos
            }
        
            // Realizar la consulta
            $contratosPorDia = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->whereBetween('FECHA', [$corte->fecha_inicio, $corte->fecha_fin])
                ->selectRaw('DATE(FECHA) as fecha, COUNT(*) as total_contratos')
                ->groupBy('fecha')
                ->get();
          
            // Actualizar las fechas con los valores de la consulta
            foreach ($contratosPorDia as $contrato) {
                $isHoliday = CalendarioColombia::date($contrato->fecha)->isHoliday();
                if($isHoliday){
                    $contadorFestivos += $contrato->total_contratos;
                }
                $fechas[$contrato->fecha] = $contrato->total_contratos;
                $sumaInspecciones += $contrato->total_contratos;
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
            $datosInspector['festivos'] = $contadorFestivos;
            if($sumaInspecciones === 0 && $inspector->state === 0){
                continue;
            }
            $produccionInspector[] = $datosInspector;
        
           
        }
        $tiempoFin = microtime(true);

        // Calcula la diferencia en milisegundos
        $tiempoTotal = ($tiempoFin - $tiempoInicio) * 1000;
        $reponse =[
            'diasIntermedios' => $diasIntermedios,
            'produccionInspector' => $produccionInspector,
            'tiempo_respuesta' => $tiempoTotal        ];
        
        return response()->json($reponse);
    }
}
