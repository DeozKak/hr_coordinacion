<?php

namespace App\Http\Controllers;

use App\Models\tbl_insp_cali;
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
        //dd($request->all());

        $mesAnio = $request->input('mesAnio');
        $produccionFiltrada = [];
        $arrayMultasRod = [];
        $arrayInspectores = [];
        $arraySalAux = [];

        $cortesProduccion = tbl_produccion_corte::with('corte')
        ->where('fecha_fin', 'like', '%' . $mesAnio . '%')
        ->get();

        // consultamos la tabla de nomina multas con el mes y anio correspondiente
        $multasRodamiento = TblNominaMultas::where('fecha',$mesAnio)
        ->get();

        // consultamos todos los inspectores
        $inspectores = tbl_insp_cali::all();

        //consultamos el salario minimo y el auxilio de transporte
        $parametroSalMinAux = TblParametroSalAux::where('fecha_inicio', '<=', $mesAnio)
        ->where('fecha_fin', '>=', $mesAnio)
        ->first();
        if ($parametroSalMinAux) {
            $arraySalAux = [
                'salarioMinimo' => $parametroSalMinAux->salario_minimo,
                'auxilioTransporte' => $parametroSalMinAux->auxilio_transporte,
                'salud' => $parametroSalMinAux->salud,
                'pension' => $parametroSalMinAux->pension,
                'arl' => $parametroSalMinAux->arl,
                'caja' => $parametroSalMinAux->caja,
                'prima' => $parametroSalMinAux->prima,
                'cesantias' => $parametroSalMinAux->cesantias,
                'intCesantias' => $parametroSalMinAux->intCesantias,
                'vacaciones' => $parametroSalMinAux->vacaciones
            ];
        }else{
            $arraySalAux = [
                'salarioMinimo' => 0,
                'auxilioTransporte' => 0,
                'salud' => 0,
                'pension' => 0,
                'arl' => 0,
                'caja' => 0,
                'prima' => 0,
                'cesantias' => 0,
                'intCesantias' => 0,
                'vacaciones' => 0
            ];
        }

        foreach($inspectores as $inspector){
            $arrayInspectores[] = [
                'cedula' => $inspector->cedula,
                'aprendiz' => $inspector->aprendiz
            ];
        }

        foreach($multasRodamiento as $item){
            $arrayMultasRod[] = [
                'cc_operario' => $item->cc_operario,
                'multa' => $item->multa,
            ];
        }

        foreach($cortesProduccion as $corte){
            $data = json_decode($corte->corte->data, true);
            $produccionFiltrada[] = [
                'data' => $data,
                'multas' => $arrayMultasRod,
                'inspectores' => $arrayInspectores,
                'salariosAux' => $arraySalAux
            ];
        }
        return response()->json($produccionFiltrada);
    }

    public function guardarMultaRodamiento(Request $request){
        $multa = $request->input('multa');
        $fecha = $request->input('fecha');
        $cedulaOperario = $request->input('ccOperario');

        $multasRodamiento = TblNominaMultas::where('fecha', $fecha)
        ->where('cc_operario', $cedulaOperario)
        ->first();

        if ($multasRodamiento) {
            if($multa == null){
                $multasRodamiento->multa = $multasRodamiento['multa'];
            }else{
                $multasRodamiento->multa = $multa;
            }

        } else {
            $multasRodamiento = new TblNominaMultas();
            $multasRodamiento->cc_operario = $cedulaOperario;

            if($multa == null){
                $multasRodamiento->multa = 0;
            }else{
                $multasRodamiento->multa = $multa;
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
        $salud = $request->input('salud');
        $pension = $request->input('pension');
        $arl = $request->input('arl');
        $caja = $request->input('caja');
        $prima = $request->input('prima');
        $cesantias = $request->input('cesantias');
        $intCesantias = $request->input('intCesantias');
        $vacaciones = $request->input('vacaciones');

        // Validación de fechas vacías
        if ($fechaInicio == "" || $fechaFin == "") {
            return response()->json(['status' => 1]); // Fechas son obligatorias
        }

        // Validación de fecha de inicio mayor que fecha de fin
        if ($fechaFin < $fechaInicio) {
            return response()->json(['status' => 2]); // La fecha de inicio no puede ser mayor a la de fin
        }

        // Validación de datos numéricos
        if (!is_numeric($salMin) || !is_numeric($auxTrans) || $salud == "" ||
            $pension == "" || $arl == "" || $caja == "" || $prima == "" ||
            $cesantias == "" || $intCesantias == "" || $vacaciones == "") {
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
                'salud' => $salud,
                'pension' => $pension,
                'arl' => $arl,
                'caja' => $caja,
                'prima' => $prima,
                'cesantias' => $cesantias,
                'intCesantias' => $intCesantias,
                'vacaciones' => $vacaciones
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
        $salud = $request->input('salud');
        $pension = $request->input('pension');
        $arl = $request->input('arl');
        $caja = $request->input('caja');
        $prima = $request->input('prima');
        $cesantias = $request->input('cesantias');
        $intCesantias = $request->input('intCesantias');
        $vacaciones = $request->input('vacaciones');

        // Validación de fechas vacías
        if ($fechaInicio == "" || $fechaFin == "") {
            return response()->json(['status' => 1]); // Fechas son obligatorias
        }

        // Validación de fecha de inicio mayor que fecha de fin
        if ($fechaFin < $fechaInicio) {
            return response()->json(['status' => 2]); // La fecha de inicio no puede ser mayor a la de fin
        }

        if (!is_numeric($salMin) || !is_numeric($auxTrans) || $salud == "" ||
            $pension == "" || $arl == "" || $caja == "" || $prima == "" ||
            $cesantias == "" || $intCesantias == "" || $vacaciones == "") {
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
                $registroActual->auxilio_transporte == $auxTrans &&
                $registroActual->salud == $salud &&
                $registroActual->pension == $pension &&
                $registroActual->arl == $arl &&
                $registroActual->caja == $caja &&
                $registroActual->prima == $prima &&
                $registroActual->cesantias == $cesantias &&
                $registroActual->intCesantias == $intCesantias &&
                $registroActual->vacaciones == $vacaciones
            ) {
                return response()->json(['status' => 7]);
            } else {
                // Realizamos el registro de las fechas
                $actualizar = TblParametroSalAux::where('id', $id)->update([
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'salario_minimo' => $salMin,
                    'auxilio_transporte' => $auxTrans,
                    'salud' => $salud,
                    'pension' => $pension,
                    'arl' => $arl,
                    'caja' => $caja,
                    'prima' => $prima,
                    'cesantias' => $cesantias,
                    'intCesantias' => $intCesantias,
                    'vacaciones' => $vacaciones
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
