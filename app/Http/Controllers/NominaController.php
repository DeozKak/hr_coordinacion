<?php

namespace App\Http\Controllers;

use App\Models\tbl_produccion_corte;
use App\Models\TblNominaMultas;
use App\Models\TblParametroSalAux;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NominaController extends Controller
{
    public function getReporteNomina()
    {
        return view('nomina.nomina');
    }

    public function postReporteNomina(Request $request)
    {
        $mesAnio = $request->input('mesAnio');
        $produccionFiltrada = [];
        $arrayMultasRod = [];
       
        $cortesProduccion = tbl_produccion_corte::with('corte')
        ->where('fecha_fin', 'like', '%' . $mesAnio . '%')
        ->get();

        // consultamos la tabla de nomina multas con el mes y anio correspondiente
        $multasRodamiento = TblNominaMultas::where('fecha',$mesAnio) 
        ->get();

        foreach($multasRodamiento as $item){
            $arrayMultasRod[] = [
                'cc_operario' => $item->cc_operario,
                'multa' => $item->multa,
                'rodamiento' => $item->rodamiento
            ];
        }

        foreach($cortesProduccion as $corte){
            $data = json_decode($corte->corte->data, true);
            $produccionFiltrada[] = [
                'data' => $data,
                'multas' => $arrayMultasRod
            ];
        }
        
        return response()->json($produccionFiltrada);
    }

    public function guardarMultaRodamiento(Request $request){
        $multa = $request->input('multa');
        $fecha = $request->input('fecha');
        $cedulaOperario = $request->input('ccOperario');
        $rodamiento = $request->input('rodamiento');

        $multasRodamiento = TblNominaMultas::where('fecha', $fecha)
        ->where('cc_operario', $cedulaOperario)
        ->first();

        if ($multasRodamiento) {
            if($multa == null){
                $multasRodamiento->multa = $multasRodamiento['multa'];
            }else{
                $multasRodamiento->multa = $multa;
            }
            if($rodamiento == null){
                $multasRodamiento->rodamiento = $multasRodamiento['rodamiento'];
            }else{
                $multasRodamiento->rodamiento = $rodamiento;
            }
        } else {
            $multasRodamiento = new TblNominaMultas();
            $multasRodamiento->cc_operario = $cedulaOperario;

            if($multa == null){
                $multasRodamiento->multa = 0;
            }else{
                $multasRodamiento->multa = $multa;
            }

            if($rodamiento == null){
                $multasRodamiento->rodamiento = 325000;
            }else{
                $multasRodamiento->rodamiento = $rodamiento;
            }
            $multasRodamiento->fecha = $fecha;
        }

        $insertar = $multasRodamiento->save();

        if($insertar){
            echo 1;
        }else{
            echo 2;
        }
    }

    public function parametrizarSalarioAux(){

        $parametroSalarioAux = TblParametroSalAux::all();

        return view('nomina.parametrizarSalarioAux', compact('parametroSalarioAux'));
    }

    public function guardarSalarioAux(Request $request)
    {
        $fechaInicio = $request->input('fechaSalAuxInicio');
        $fechaFin = $request->input('fechaSalAuxFin');
        $salMin = intval($request->input('salMin'));
        $auxTrans = intval($request->input('auxTrans'));

        // Validación de fechas vacías
        if ($fechaInicio == "" || $fechaFin == "") {
            return response()->json(['status' => 1]); // Fechas son obligatorias
        }

        // Validación de fecha de inicio mayor que fecha de fin
        if ($fechaFin < $fechaInicio) {
            return response()->json(['status' => 2]); // La fecha de inicio no puede ser mayor a la de fin
        }

        // Validación de datos numéricos
        if (!is_numeric($salMin) || !is_numeric($auxTrans)) {
            return response()->json(['status' => 3]); // Datos inválidos
        }

        // Validar si las fechas cruzan con los registros existentes
        $fechaParametros = DB::select(
            "SELECT * FROM tbl_parametro_sal_aux 
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
            $insertar = TblParametroSalAux::create([
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'salario_minimo' => $salMin,
                'auxilio_transporte' => $auxTrans,
            ]);

            if ($insertar) {
                return response()->json(['status' => 5]);
            } else {
                return response()->json(['status' => 6]);
            }
        }
    }

    public function actualizarSalarioAux(Request $request){
        $id = $request->input('id');
        $fechaInicio = $request->input('fechaSalAuxInicio');
        $fechaFin = $request->input('fechaSalAuxFin');
        $salMin = intval($request->input('salMin'));
        $auxTrans = intval($request->input('auxTrans'));

        // Validación de fechas vacías
        if ($fechaInicio == "" || $fechaFin == "") {
            return response()->json(['status' => 1]); // Fechas son obligatorias
        }

        // Validación de fecha de inicio mayor que fecha de fin
        if ($fechaFin < $fechaInicio) {
            return response()->json(['status' => 2]); // La fecha de inicio no puede ser mayor a la de fin
        }

        if (!is_numeric($salMin) || !is_numeric($auxTrans)) {
            return response()->json(['status' => 3]); // Datos inválidos
        }

        // Validar si las fechas cruzan con los registros existentes
        $fechaParametros = DB::select(
            "SELECT * FROM tbl_parametro_sal_aux 
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
            $registroActual = TblParametroSalAux::find($id);
            // Comparamos los valores antes de actualizar
            if (
                $registroActual->fecha_inicio == $fechaInicio &&
                $registroActual->fecha_fin == $fechaFin &&
                $registroActual->salario_minimo == $salMin &&
                $registroActual->auxilio_transporte == $auxTrans
            ) {
                return response()->json(['status' => 7]);
            } else {
                // Realizamos el registro de las fechas
                $actualizar = TblParametroSalAux::where('id', $id)->update([
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'salario_minimo' => $salMin,
                    'auxilio_transporte' => $auxTrans,
                    
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
