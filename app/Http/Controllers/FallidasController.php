<?php

namespace App\Http\Controllers;

use App\Models\Bitacoras\tbl_bitacora_fallida;
use App\Models\Produccion\tbl_produccion_corte;
use App\Models\Produccion\tbl_produccion_historico;
use App\Models\tbl_insp_cali;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FallidasController extends Controller
{
    public function index(){
        return view('produccion.fallidas');
    }

    public function getData(Request $request){

        if (session('id_corte') || $request->idCorteDetalles) {
            $idCorte = session('id_corte') ?? $request->idCorteDetalles;
            $corte = tbl_produccion_corte::find($idCorte);

            $exist = tbl_produccion_historico::where('id_corte', $corte->id)->exists();

            if ($exist) {
                $historico = tbl_produccion_historico::where('id_corte', $corte->id)->first();
                $response = json_decode($historico->data);
                session()->forget('id_corte');
                session()->save();

                session()->put('corteEnviar', $corte);

                return response()->json($response);
            }
        } else {
            $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-MM-DD'
            $fecha_resta_un_dia = date('Y-m-d', strtotime($fecha_actual . ' -1 day'));

            $corte = tbl_produccion_corte::where('fecha_inicio', '<=', $fecha_resta_un_dia)
                ->where('fecha_fin', '>=', $fecha_resta_un_dia)
                ->first();
               // session()->put('corteEnviar', $corte);

        }


        $diasIntermedios = $this->DiasIntermedios($corte);
        if ($diasIntermedios == null) {
            return response()->json(['error' => 'No hay corte activo']);
        }
        $cantidad_dias = count($diasIntermedios);


        $fechaInicio = new \DateTime($corte->fecha_inicio);
        $fechaFin = new \DateTime($corte->fecha_fin);
        $fechaFin->modify('+1 day');
        $interval = new \DateInterval('P1D'); // Intervalo de 1 día
        $periodo = new \DatePeriod($fechaInicio, $interval, $fechaFin);

        $fechasIntermedias = [];
        foreach ($periodo as $fecha) {
            $fechasIntermedias[] = $fecha->format('Y-m-d');
        }

        $inspectores = tbl_insp_cali::orderBy('apellidos', 'asc')->get();
        // sacar produccion de cada inspector
        $fallidas = array();

        foreach ($inspectores as $inspector) {
            //preparacion fechas para array de fechas
             // Generar todas las fechas en el rango
             $fechaInicio = Carbon::parse($corte->fecha_inicio);
             $fechaFin = Carbon::parse($corte->fecha_fin);
             $fechas = [];
             $sumaFallidas = 0;

            for ($date = $fechaInicio; $date->lte($fechaFin); $date->addDay()) {
                $fechas[$date->format('Y-m-d')] = ""; // Inicializa todas las fechas con 0 contratos
            }
             // Realizar la consulta
             $contratosPorDia = tbl_bitacora_fallida::where('CC_OPERARIO', '=', $inspector->cedula)
             ->where( 'FECHA', '>=', $corte->fecha_inicio)
             ->where('FECHA', '<=', $corte->fecha_fin)
             ->where('TIPO_TRABAJO', '!=', 'FI-29 revisión periódica línea matriz')
             ->select(DB::raw('DATE(FECHA) as fecha, COUNT(*) as total_contratos'))
             ->groupBy('fecha')
             ->get();
             if ($contratosPorDia->sum('total_contratos') == 0) {
                 continue;
             }

             foreach ($contratosPorDia as $contrato) {
                $fechas[$contrato->fecha] = $contrato->total_contratos;
                $sumaFallidas = $sumaFallidas + $contrato->total_contratos;
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
            $datosInspector['total'] = $sumaFallidas;
            $fallidas[] = $datosInspector;
        }
        $response = [
            'diasIntermedios' => $diasIntermedios,
            'fechasIntermedias' => $fechasIntermedias,
            'produccionInspector' => $fallidas
        ];
      return response()->json($response);
    }

    public function DiasIntermedios($corte)
    {
        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $fechaActual = date('Y-m-d');

        if ($corte != null) {
            $fechaInicio = new \DateTime($corte->fecha_inicio);
            $fechaFin = new \DateTime($corte->fecha_fin);
            $fechaFin->modify('+1 day');
        } else {
            return null;
        }

        $interval = new \DateInterval('P1D'); // Intervalo de 1 día
        $periodo = new \DatePeriod($fechaInicio, $interval, $fechaFin);

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

    public function detallesDiario($fecha, $inspector)
    {
        $corte = session('corteEnviar');

        $contratosDia = tbl_bitacora_fallida::selectRaw("tbl_bitacora_fallidas.id, CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS nombre_completo, tbl_bitacora_fallidas.CC_OPERARIO, tbl_bitacora_fallidas.MUNICIPIO, tbl_bitacora_fallidas.FECHA,
        tbl_bitacora_fallidas.No_ACTA, tbl_bitacora_fallidas.TIPO_TRABAJO, tbl_bitacora_fallidas.CONTRATO, tbl_bitacora_fallidas.ORDEN_TRABAJO, tbl_bitacora_fallidas.ORDEN_EXT, tbl_bitacora_fallidas.CATEGORIA, tbl_bitacora_fallidas.RESULTADO_CIERRE")
        ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_fallidas.CC_OPERARIO')
        ->where('tbl_bitacora_fallidas.CC_OPERARIO', '=', $inspector)
        ->where('tbl_bitacora_fallidas.FECHA', '=', $fecha)
        ->get();


        return response()->json($contratosDia);
    }
}
