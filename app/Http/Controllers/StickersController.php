<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\tbl_controlstick_semana;
use App\Models\tbl_controlstick_historico;
use App\Models\tbl_insp_cali;
use App\Models\tbl_bitacora_contrato;
use Illuminate\Support\Facades\DB;
use IntlDateFormatter;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class StickersController extends Controller
{

    public function index()
    {
        $fecha_actual = date('Y-m-d');

        $semana = tbl_controlstick_semana::where('fecha_inicio', '<=', $fecha_actual)
            ->where('fecha_fin', '>=', $fecha_actual)
            ->first();

        if (is_null($semana)) {

            $dia_semana = date('w', strtotime($fecha_actual));

            // Calcular la diferencia en días para llegar al lunes (inicio de la semana)
            $dias_a_restar = ($dia_semana == 0) ? 6 : $dia_semana - 1;

            // Calcular la fecha de inicio de la semana
            $fecha_inicio = date('Y-m-d', strtotime("-$dias_a_restar days", strtotime($fecha_actual)));

            // Calcular la fecha de fin de la semana (domingo)
            $fecha_fin = date('Y-m-d', strtotime("+6 days", strtotime($fecha_inicio)));

            // Crear un formateador de fechas en español para el nombre del mes
            $formateadorMes = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'UTC', IntlDateFormatter::GREGORIAN, 'MMMM');

            // Obtener el nombre del mes de la fecha de inicio
            $mes_inicio = $formateadorMes->format(new \DateTime($fecha_inicio));

            // Obtener el nombre del mes de la fecha de fin
            $mes_fin = $formateadorMes->format(new \DateTime($fecha_fin));

            // Obtener el año (puedes usar $fecha_inicio o $fecha_fin)
            $año = date('Y', strtotime($fecha_inicio));

            // Concatenar los valores, verificando si el mes es el mismo
            if ($mes_inicio == $mes_fin) {
                $resultado = "$mes_inicio / $año";
            } else {
                $resultado = "$mes_inicio - $mes_fin / $año";
            }

            $semana = new tbl_controlstick_semana;
            $semana->mes_año = $resultado;
            $semana->fecha_inicio = $fecha_inicio;
            $semana->fecha_fin = $fecha_fin;
            $semana->save();
            $semana = tbl_controlstick_semana::all();
            return view('stickers.index', compact('semana'));
        }

        $semana = tbl_controlstick_semana::all();

        return view('stickers.index', compact('semana'));
    }

    public function show($id)
    {
        return view('stickers.show', compact('id'));
    }

    public function getData($id)
    {
        
         $fecha_actual = date('Y-m-d');
       /*  $fecha_actual = "2024-12-16"; */
        $verf_semana = tbl_controlstick_semana::find($id);
        if ($fecha_actual >= $verf_semana->fecha_inicio && $fecha_actual <= $verf_semana->fecha_fin) {
        } else {
            $historico = tbl_controlstick_historico::where('id_semana', $id)->first();
            
            // Obtener las fechas de la semana
            $lunes = date('Y-m-d', strtotime($verf_semana->fecha_inicio));
            $martes = date('Y-m-d', strtotime($lunes . ' + 1 day'));
            $miercoles = date('Y-m-d', strtotime($lunes . ' + 2 days'));
            $jueves = date('Y-m-d', strtotime($lunes . ' + 3 days'));
            $viernes = date('Y-m-d', strtotime($lunes . ' + 4 days'));
            $sabado = date('Y-m-d', strtotime($lunes . ' + 5 days'));
            $domingo = date('Y-m-d', strtotime($lunes . ' + 6 days'));

            // Construir los nestedHeaders con las fechas

            $response = json_decode($historico->Data); // $response es un objeto stdClass

            // Agregar el nuevo registro como una propiedad del objeto
            $response->indicador_lectura = 1;
           
             /*  $response = [
                'nestedHeaders' => $nestedHeaders,
                'registros' => $historico,
                'indicador_lectura' => 1
            ];  */
            return response()->json($response);
        }

        $user = Auth::user();

        $inspectores = tbl_insp_cali::selectRaw('CONCAT(apellidos, " ", nombres) AS nombre_completo, cedula')->where('state', 1)
            ->orderBy('apellidos', 'asc')
            ->get();

        $semana = tbl_controlstick_semana::find($id);
        $interval = new \DateInterval('P1D'); // Intervalo de 1 día
        $fechaInicio = new \DateTime($semana->fecha_inicio);
        $fechaFin = new \DateTime($semana->fecha_fin);
        $fechaFin->modify('+1 day');
        $periodo = new \DatePeriod($fechaInicio, $interval, $fechaFin);

        //preparar la fecha del dia anterior para consulta de saldos de la semana anterior
        $fechaInicio->modify('-1 day');
        $fechaAnterior = $fechaInicio->format('Y-m-d'); // Formatear la fecha


        $semana_anterior = tbl_controlstick_semana::where('fecha_fin', '=', $fechaAnterior)
            ->first();
        //inicilizar variable
        $registro_anterior = [];
        if ($semana_anterior) { // Verificar si $semana_anterior no es null
            $historico_semana_anterior = tbl_controlstick_historico::where('id_semana', '=', $semana_anterior->id)->first();
            $registro_anterior = json_decode($historico_semana_anterior->Data);
        }
        $fechasIntermedias = [];
        foreach ($periodo as $fecha) {
            $fechasIntermedias[] = $fecha->format('Y-m-d');
        }

        $registros = [];
        foreach ($inspectores as $indexIns => $inspector) {
            //Inicialiar Variables de dias
            $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

            foreach ($dias as $dia) {
                ${$dia . 'Cert'} = "";
                ${$dia . 'Rech'} = "";
                ${$dia . 'Matriz'} = "";
            }

            // Realizar la consulta
            $contratosPorDia = tbl_bitacora_contrato::where('CC_OPERARIO', '=', $inspector->cedula)
                ->where('FECHA', '>=', $semana->fecha_inicio)
                ->where('FECHA', '<=', $semana->fecha_fin)
                ->where('state', '=', 1)
                ->where('TIPO_TRABAJO', '!=', 'FI-29 revisión periódica línea matriz')
                ->select(
                    DB::raw('DATE(FECHA) as fecha'),
                    DB::raw('SUM(CASE WHEN RESULTADO_CIERRE IN ("CERTIFICADA", "CERTIFICADA CON NOVEDADES") THEN 1 ELSE 0 END) as total_contratos_cert'),
                    DB::raw('SUM(CASE WHEN RESULTADO_CIERRE IN ("INSPECCIONADA CON DEFECTO CRITICO VALLE", "INSPECCIONADA CON DEFECTO NO CRITICO VALLE") THEN 1 ELSE 0 END) as total_contratos_rech'),
                    DB::raw('SUM(CASE WHEN TIPO_TRABAJO IN ("FI-29 revisión periódica línea matriz","FI-31 REVISIÓN NUEVA LINEA MATRIZ") THEN 1 ELSE 0 END) as total_revisiones_matriz')
                )
                ->groupBy('fecha')
                ->get()->toArray();

            if ($contratosPorDia == []) {
                $ins_activado = tbl_insp_cali::where('cedula', $inspector->cedula)->first();
                if ($ins_activado->state == 0) {
                    continue;
                }
            }
            //consulta para cargar los datos guardados
            $data = tbl_controlstick_historico::where('id_semana', $id)->first();

            if ($data !== null) {
                $data = json_decode($data->Data);
            }
            foreach ($contratosPorDia as $contrato) {

                switch ($this->diaDeLaSemana($contrato['fecha'])) {
                    case 'Monday':

                        $lunesCert = $contrato['total_contratos_cert'];
                        $lunesRech = $contrato['total_contratos_rech'];
                        $lunesMatriz = $contrato['total_revisiones_matriz'];
                        break;
                    case 'Tuesday':

                        $martesCert = $contrato['total_contratos_cert'];
                        $martesRech = $contrato['total_contratos_rech'];
                        $martesMatriz = $contrato['total_revisiones_matriz'];
                        break;
                    case 'Wednesday':
                        $miercolesCert = $contrato['total_contratos_cert'];
                        $miercolesRech = $contrato['total_contratos_rech'];
                        $miercolesMatriz = $contrato['total_revisiones_matriz'];
                        break;
                    case 'Thursday':
                        $juevesCert = $contrato['total_contratos_cert'];
                        $juevesRech = $contrato['total_contratos_rech'];
                        $juevesMatriz = $contrato['total_revisiones_matriz'];
                        break;
                    case 'Friday':
                        $viernesCert = $contrato['total_contratos_cert'];
                        $viernesRech = $contrato['total_contratos_rech'];
                        $viernesMatriz = $contrato['total_revisiones_matriz'];
                        break;
                    case 'Saturday':
                        $sabadoCert = $contrato['total_contratos_cert'];
                        $sabadoRech = $contrato['total_contratos_rech'];
                        $sabadoMatriz = $contrato['total_revisiones_matriz'];
                        break;
                    case 'Sunday':
                        $domingoCert = $contrato['total_contratos_cert'];
                        $domingoRech = $contrato['total_contratos_rech'];
                        $domingoMatriz = $contrato['total_revisiones_matriz'];

                        break;
                }
            }
            $matrices = [
                $lunesMatriz ?? "",
                $martesMatriz ?? "",
                $miercolesMatriz ?? "",
                $juevesMatriz ?? "",
                $viernesMatriz ?? "",
                $sabadoMatriz ?? "",
                $domingoMatriz ?? ""
            ];

            $certificados = [
                $lunesCert ?? "",
                $martesCert ?? "",
                $miercolesCert ?? "",
                $juevesCert ?? "",
                $viernesCert ?? "",
                $sabadoCert ?? "",
                $domingoCert ?? ""
            ];

            $rechazados = [
                $lunesRech ?? "",
                $martesRech ?? "",
                $miercolesRech  ?? "",
                $juevesRech ?? "",
                $viernesRech ?? "",
                $sabadoRech ?? "",
                $domingoRech ?? ""
            ];

            // Inicializar las variables en caso de que no estén definidas
            $certificados = array_map(function ($valor) {
                return isset($valor) ? $valor : 0;
            }, $certificados);
            $rechazados = array_map(function ($valor) {
                return isset($valor) ? $valor : 0;
            }, $rechazados);

            //validacion primera vez

            if ($data == null || $data->registros == []) {
                $amarillos = 0;
                $rojos = 0;
            } else {
                $amarillos = $data->registros->{$inspector->cedula}->AMARILLOS;
                $rojos = $data->registros->{$inspector->cedula}->ROJOS;
            }


            $saldoAmarillo = $amarillos - array_sum($certificados);
            $saldoRojo = $rojos - array_sum($rechazados);
            $saldoRojo_matriz = $saldoRojo - array_sum($matrices);
        
            $registros[$inspector->cedula] = [
                'cc_operario' => $inspector->cedula,
                'nombre_completo' => $inspector->nombre_completo,
                'saldoAnteriorAmarillo' => isset($registro_anterior->registros->{$inspector->cedula}->saldoAmarillo) 
                            ? $registro_anterior->registros->{$inspector->cedula}->saldoAmarillo 
                            : 0,
                'saldoAnteriorRojo' => isset($registro_anterior->registros->{$inspector->cedula}->saldoRojo) 
                ? $registro_anterior->registros->{$inspector->cedula}->saldoRojo 
                : 0,
                'AMARILLOS' => $amarillos,
                'ROJOS' =>  $rojos,
                'lunesCert' => $lunesCert ?? "",
                'lunesRech' => $lunesRech ?? "",
                'lunesMatriz' => $lunesMatriz ?? "",
                'martesCert' => $martesCert ?? "",
                'martesRech' => $martesRech ?? "",
                'martesMatriz' => $martesMatriz ?? "",
                'miercolesCert' => $miercolesCert ?? "",
                'miercolesRech' => $miercolesRech ?? "",
                'miercolesMatriz' => $miercolesMatriz ?? "",
                'juevesCert' => $juevesCert ?? "",
                'juevesRech' => $juevesRech ?? "",
                'juevesMatriz' => $juevesMatriz ?? "",
                'viernesCert' => $viernesCert ?? "",
                'viernesRech' => $viernesRech ?? "",
                'viernesMatriz' => $viernesMatriz ?? "",
                'sabadoCert' => $sabadoCert ?? "",
                'sabadoRech' => $sabadoRech ?? "",
                'sabadoMatriz' => $sabadoMatriz ?? "",
                'domingoCert' => $domingoCert ?? "",
                'domingoRech' => $domingoRech ?? "",
                'domingoMatriz' => $domingoMatriz ?? "",
                'saldoAmarillo' => $saldoAmarillo,
                'saldoRojo' =>  $saldoRojo_matriz
            ];
        }

        // Obtener las fechas de la semana
        $lunes = date('Y-m-d', strtotime($semana->fecha_inicio));
        $martes = date('Y-m-d', strtotime($lunes . ' + 1 day'));
        $miercoles = date('Y-m-d', strtotime($lunes . ' + 2 days'));
        $jueves = date('Y-m-d', strtotime($lunes . ' + 3 days'));
        $viernes = date('Y-m-d', strtotime($lunes . ' + 4 days'));
        $sabado = date('Y-m-d', strtotime($lunes . ' + 5 days'));
        $domingo = date('Y-m-d', strtotime($lunes . ' + 6 days'));

        // Construir los nestedHeaders con las fechas
        $nestedHeaders = [
            [
                '',
                'ENTREGA DE STICKER',
                (object)['label' => 'SALDO SEMANA ANTERIOR', 'colspan' => 2],
                (object)['label' => 'AMARILLOS', 'colspan' => 1],
                (object)['label' => 'ROJOS', 'colspan' => 1],
                (object)['label' => 'LUNES ' . $lunes, 'colspan' => 3],
                (object)['label' => 'MARTES ' . $martes, 'colspan' => 3],
                (object)['label' => 'MIERCOLES ' . $miercoles, 'colspan' => 3],
                (object)['label' => 'JUEVES ' . $jueves, 'colspan' => 3],
                (object)['label' => 'VIERNES ' . $viernes, 'colspan' => 3],
                (object)['label' => 'SABADO ' . $sabado, 'colspan' => 3],
                (object)['label' => 'DOMINGO ' . $domingo, 'colspan' => 3],
                (object)['label' => 'SALDO', 'colspan' => 2],
            ],
            [
                '',
                'Inspectores',
                'AMARILLOS',
                'ROJOS',
                'ENTREGA',
                'ENTREGA',
                'CERTIFICADAS',
                'RECHAZADAS',
                'MATRIZ',
                'CERTIFICADAS',
                'RECHAZADAS',
                'MATRIZ',
                'CERTIFICADAS',
                'RECHAZADAS',
                'MATRIZ',
                'CERTIFICADAS',
                'RECHAZADAS',
                'MATRIZ',
                'CERTIFICADAS',
                'RECHAZADAS',
                'MATRIZ',
                'CERTIFICADAS',
                'RECHAZADAS',
                'MATRIZ',
                'CERTIFICADAS',
                'RECHAZADAS',
                'MATRIZ',
                'AMARILLOS',
                'ROJOS'
            ],
        ];

        $historico = tbl_controlstick_historico::where('id_semana', $id)->exists();

        $response = [
            'nestedHeaders' => $nestedHeaders,
            'registros' => $registros
        ];
        if ($historico) {
            $historico = tbl_controlstick_historico::where('id_semana', $id)->first();
            $historico->Data = json_encode($response);
            $historico->save();
        } else {
            $historico = new tbl_controlstick_historico();
            $historico->id_semana = $id;
            $historico->Data = json_encode($response);
            $historico->save();
        }

        $response = json_decode($historico->Data);

        //validacion para sacar operarios por supervisor
        if (!$user->haspermissionTo('control_stickers')) {
            $cc_operarios = tbl_insp_cali::select('cedula')->where('SUPERVISOR', $user->id)->get();

            // Convertir la colección de objetos a un array simple de cédulas
            $cc_operarios = $cc_operarios->pluck('cedula')->toArray();

            // Convertir $response->registros a un array
            $response->registros = get_object_vars($response->registros);

            // Filtrar los registros del response
            $response->registros = array_filter(
                $response->registros,
                function ($registro) use ($cc_operarios) {
                    return in_array($registro->cc_operario, $cc_operarios);
                }
            );
        }
        if ($response->registros == []) {
            session()->flash('error', 'No tiene permisos para ver el reporte');
            return response()->json(['warning' => 'No tiene permisos para ver el reporte']);
        }
        return response()->json($response);
    }

    public function update(Request $request)
    {
        try {
            $consulta = tbl_controlstick_historico::where('id_semana', '=', $request->id_semana)->first();

            $data = json_decode($consulta->Data);

            $data->registros->{$request->cc_operario}->{$request->prop} = $request->newValue;

            $consulta->Data = json_encode($data);
            $consulta->save();

            return response()->json(['message' => 'OK']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e]);
        }
    }


    function diaDeLaSemana($fecha)
    {
        // Convertir la fecha a formato de tiempo Unix
        $timestamp = strtotime($fecha);

        // Obtener el día de la semana en español (lunes a domingo)
        $diaSemana = date("l", $timestamp);

        // Convertir la primera letra a mayúscula
        $diaSemana = ucfirst($diaSemana);

        return $diaSemana;
    }
}
