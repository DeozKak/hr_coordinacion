<?php

namespace App\Http\Controllers;

use App\Models\tbl_dv_insp;
use App\Models\tbl_temp_contrato;
use App\Models\User;
use App\Models\tbl_insp_cali;
use App\Models\tbl_bitacora_archivo;
use App\Models\tbl_bitacora_contrato;
use App\Models\Movilidad;
use App\Notifications\Mod_Devolucion;
use App\Notifications\Bitacora;
use DateTime;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\tbl_localidades_municipio;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Models\tbl_bitacoras_causal;
use App\Notifications\devolucion;
use App\Http\Controllers\AutoGuardadoController;


class BitacoraController extends Controller
{
    public function ver()
    {
        $supervisores = Auth::user();
        $id_user = $supervisores->id;
        if ($supervisores->hasRole('Supervisor')) {
          
            $temp = tbl_bitacora_archivo::where('id_usuario','=',$id_user)->where('finished','=',0)->first();
        
            
            if(!$temp){
               
                return view('bitacoras.generar', compact('supervisores'));
            }
          
           
            session()->flash('warning', 'Ya tienes una bitácora en proceso. ¿Deseas continuar?');

            return view('bitacoras.generar', compact('supervisores','temp'));
        }
        $supervisores = User::role('Supervisor')->get();

        $temp = tbl_bitacora_archivo::where('id_usuario','=',$id_user)->where('finished','=',0)->first();
       
        if(!$temp){
           
            return view('bitacoras.generar', compact('supervisores'));
        }
      
       
        session()->flash('warning', 'Ya tienes una bitácora en proceso. ¿Deseas continuar?');
        return view('bitacoras.generar', compact('supervisores','temp'));
    
    }

    public function generar_bitacora(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'supervisor' => 'required',
            'archivo' => 'required'

        ], [
            'supervisor.required' => 'Por favor seleccione un supervisor',
            'archivo.required' => 'Por favor seleccione un archivo',
            'archivo.mimes' => 'El archivo seleccionado no es válido'
        ]);

        if ($validator->fails()) {
            return redirect()->route('bitacora')->withErrors($validator)->withInput()->with('error', $validator->errors()->first());
        }

        if ($request->supervisor === "0") {

            $nombreArchivo = $request->archivo->getClientOriginalName() . "Todos" . ".xls";

            $request->archivo->storeAs('uploads', $nombreArchivo);

            $rutaDestino = storage_path('app/uploads/') . $nombreArchivo;

            $excelFilePath = $rutaDestino;

            return $this->procesarArchivoExcel($excelFilePath, new AutoGuardadoController());
        }

        $supervisor = User::find($request->supervisor);

        $nombreArchivo = $request->archivo->getClientOriginalName() . $supervisor->name . ".xls";

        $request->archivo->storeAs('uploads', $nombreArchivo);

        $rutaDestino = storage_path('app/uploads/') . $nombreArchivo;

        $excelFilePath = $rutaDestino;

        return $this->procesarArchivoExcel($excelFilePath, new AutoGuardadoController(),$supervisor->name, $supervisor->id);
    }

    public function procesarArchivoExcel($excelFilePath, AutoGuardadoController $Guardado ,$nom_super = null, $id_super = null)
    {

        session(['nom_archivo' => basename($excelFilePath)]);
       
        $validacionArchivo1 = str_replace(".xls", " ", basename($excelFilePath));
        $validacionArchivo2 = str_replace("4.08", "", $validacionArchivo1);

        $exist = $Guardado->buscar($validacionArchivo2);
       
        if ($exist) {
            $data = $exist->getData(true); // Obtener datos como array asociativo
            $mensaje = $data['error'];
            return redirect()->route('bitacora')->with('error', $mensaje);
        }

        session(['super' => $nom_super]);
        //consultas a la base de datos
        if ($nom_super === null || $id_super === null) {
            $inspectores = tbl_insp_cali::where('state', 1)
                ->orderBy('apellidos', 'asc')
                ->get();
        } else {
            $inspectores = tbl_insp_cali::where('SUPERVISOR', $id_super)
                ->where('state', 1)
                ->orderBy('apellidos', 'asc')
                ->get();
        }
        $municipios = tbl_localidades_municipio::all();

        $nombres = array();
        $ids = array();
        
        
        foreach ($inspectores as $inspector) {
            $nombres[] = $inspector->apellidos . ' ' . $inspector->nombres;
            $ids[$inspector->cedula] = $inspector->id;
        }
    
        session(['ids_inspectores' => $ids]);
        $id_inspector = 1118285465;

        try {
            $spreadsheet = IOFactory::load($excelFilePath);
        } catch (\Exception $e) {
            return redirect()->route('bitacora')->with('error', 'El archivo seleccionado no es válido o no se ha seleccionado un supervisor');
        }
        if (!$spreadsheet->sheetNameExists('4.08 Bitacora Valle')) {
            return redirect()->route('bitacora')->with('error', 'El archivo seleccionado no es válido o no se ha seleccionado un supervisor');
        }

        unlink($excelFilePath);
        $causales = tbl_bitacoras_causal::all();

        $response = $Guardado->guardar($spreadsheet, $nombres, $id_super);
       
        
        if($response->isEmpty()){
            
            return redirect()->route('bitacora')->with('error', 'Error al generar la bitacora');
        
        }
        
        return view('bitacoras.tabla', compact('nombres', 'id_super', 'municipios', 'inspectores','causales','response'));
    }

    public function guardar_tabla(Request $request, User $super = null)
    {
        //variables que obtienen datos del request
        $encabezados = $request->encabezado;
        $dataTable = $request->datos;
        $indicadores = $request->indicadores;
        $valoresSeleccionados = $request->valoresSeleccionados;
        
        $datos_array = array();

        // Crear una instancia de la clase Spreadsheet
        $spreadsheet = new Spreadsheet();
        $hoja_OK = $spreadsheet->getSheetByName('Worksheet');
        $indiceFila_ok = 2;
        foreach ($dataTable as $indice => $tabla) {

            $idTabla = "$indice";

            
            
            $nombre_tabla = $tabla[0][1] ?? "Tabla $indice";
        
            $nombre_tabla = strlen($nombre_tabla) > 31 ? substr($nombre_tabla, 0, 31) : $nombre_tabla;

            // Crear una nueva hoja de cálculo para esta tabla
            $hoja = $spreadsheet->createSheet($indice);
            $hoja->setTitle($nombre_tabla);
            // setear la tabla de indicadores
            $tablaIndicadores = $indicadores[$indice];

            $hoja->setCellValue([1, 1], ".CERTIFICADA");
            $hoja->setCellValue([1, 2], "CERTIFICADA CON NOVEDADES");
            $hoja->setCellValue([1, 3], ".INSPECCIONADA CON DEFECTO CRITICO");
            $hoja->setCellValue([1, 4], ".INSPECCIONADA CON DEFECTO NO CRITICO");
            $hoja->setCellValue([1, 5], "TOTAL CONTRATOS OK");

            $hoja->setCellValue([2, 1], $tablaIndicadores["certificadaCount"]);
            $hoja->setCellValue([2, 2], $tablaIndicadores["certificadaConNovedadesCount"]);
            $hoja->setCellValue([2, 3], $tablaIndicadores["inspeccionadaConDefectoCriticoCount"]);
            $hoja->setCellValue([2, 4], $tablaIndicadores["inspeccionadaConDefectoNoCriticoCount"]);
            $hoja->setCellValue([2, 5], $tablaIndicadores["totalCount"]);


            $indiceFila = 8;
            $indicador_checkbox = 0;
            $indicador_combobox1 = 1;
            $indicador_combobox2 = 2;
            // Iterar sobre cada fila de la tabla
            foreach ($tabla as $fila) {

                // Si hay celdas de encabezado, procesarlas
                if (!empty($encabezados)) {
                    $indiceColumna = 1;
                    foreach ($encabezados as $encabezado) {
                        // Obtener el contenido del encabezado
                        $contenidoEncabezado = $encabezado;

                        // Pegar el contenido del encabezado en la hoja de cálculo
                        $hoja->setCellValue([$indiceColumna, 7], $contenidoEncabezado);

                        // Incrementar el índice de columna
                        $indiceColumna++;
                    }
                }

                // Si hay celdas de datos, procesarlas
                if (!empty($fila)) {
                    // Inicializar el índice de columna en 1
                    $indiceColumna = 1;

                    foreach ($fila as $celda) {

                        $contenidoCelda = $celda;
                        // obtener datos complementarios 60 meses y rechazos
                        $vence = $fila[18];
                        $rechazo = $fila[19];
                        // Obtener el identificador único del combobox y checkbox
                        $idCheckbox = $indicador_checkbox;
                        $idCombobox1 = $indicador_combobox1;
                        $idCombobox2 = $indicador_combobox2;

                        // Obtener la clave para buscar en los valores seleccionados
                        $claveCheckbox = "select_$idTabla" . "_$idCheckbox";
                        $clave = "select_$idTabla" . "_$idCombobox1";
                        $clave2 = "select_$idTabla" . "_$idCombobox2";
                        if ($indiceColumna === 16 && isset($valoresSeleccionados[$claveCheckbox])) {
                            $contenidoCelda = $valoresSeleccionados[$claveCheckbox];
                            if ($contenidoCelda === "false") {
                                $contenidoCelda = "NO";
                            } else {
                                $celda_color = $hoja->getCell([$indiceColumna, $indiceFila]);
                                $celda_color->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF8000');
                            }
                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoCelda);
                        }
                        if (array_key_exists($clave, $valoresSeleccionados) && $valoresSeleccionados[$clave] === "OK") {
                            $validacion = 0;
                            // copiado del campo 60 meses a la hoja OK
                            if($indiceColumna === 19){
                                $hoja_OK->setCellValue([16, $indiceFila_ok], $contenidoCelda);
                               
                                $celda_color = $hoja_OK->getCell([7, $indiceFila_ok]);

                                $celda_color->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');
                            }
                            
                            if ($indiceColumna < 16) {

                                $hoja_OK->setCellValue([$indiceColumna, $indiceFila_ok], $contenidoCelda);
                               
                                $celda_color = $hoja_OK->getCell([7, $indiceFila_ok]);

                                $celda_color->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');
                            }
                            $validacion = $validacion + 1;
                        } else {
                            $validacion = 0;
                        }
                        // Verificar si existe un valor seleccionado para este combobox y esta tabla
                        if ($indiceColumna === 17 && isset($valoresSeleccionados[$clave])) {
                            // Usar el valor seleccionado en lugar del contenido de la celda
                            $contenidoCelda = $valoresSeleccionados[$clave];
                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoCelda);
                            if ($valoresSeleccionados[$clave] === "OK") {
                                $celda_color = $hoja->getCell([8, $indiceFila]);
                                $celda_color->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');
                                $cedula_insp = $hoja->getCell([3, $indiceFila])->getValue();
                             
                                // guardar un array con todos los contratos en ok
                                $ids_inspectores = session('ids_inspectores');
                                $id_cedula = $ids_inspectores[$cedula_insp];
                                $fecha = $hoja->getCell([5, $indiceFila])->getValue();
                               if($hoja->getCell([16, $indiceFila])->getValue()===null){
                                    $hoja->setCellValue([16, $indiceFila], "NO");
                               }
                               
                                $datos_array_OK[] = array(
                                    'cc_operario' => $hoja->getCell([3, $indiceFila])->getValue(),
                                    'municipio' => $hoja->getCell([4, $indiceFila])->getValue(),
                                    'fecha_inspeccion' => $fecha,
                                    'no_acta' => $hoja->getCell([6, $indiceFila])->getValue(),
                                    'tipo_de_trabajo' => $hoja->getCell([7, $indiceFila])->getValue(),
                                    'contrato' => $hoja->getCell([8, $indiceFila])->getValue(),
                                    'orden_de_trabajo' => $hoja->getCell([9, $indiceFila])->getValue(),
                                    'orden_externa' => $hoja->getCell([10, $indiceFila])->getValue(),
                                    'categoria' => $hoja->getCell([11, $indiceFila])->getValue(),
                                    'resultado' => $hoja->getCell([12, $indiceFila])->getValue(),
                                    'hora_inicio' => $hoja->getCell([13, $indiceFila])->getValue(),
                                    'hora_fin' => $hoja->getCell([14, $indiceFila])->getValue(),
                                    '4_recintos' => $hoja->getCell([16, $indiceFila])->getValue(),
                                    'vence' => $vence,
                                    'rechazo' => $rechazo
                                );
                                                                                      
                            } else {

                                $celda_color = $hoja->getCell([8, $indiceFila]);
                                $celda_color->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
                            }
                        } elseif (array_key_exists($clave, $valoresSeleccionados) && $valoresSeleccionados[$clave] === "DV" && $indiceColumna === 18 && isset($valoresSeleccionados[$clave2])) {
                            if ($valoresSeleccionados[$clave2] === '--SELECCIONE CAUSAL--') {
                                header('Content-Type: application/json');
                                echo json_encode(['error' => 'Por favor, seleccione una causal para los contratos en estado de devolucion']);
                                exit;
                            }
                            $contenidoCelda = $valoresSeleccionados[$clave2];
                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoCelda);

                            $cedula_insp = $hoja->getCell([3, $indiceFila])->getValue();
                            $ids_inspectores = session('ids_inspectores');
                            $id_cedula = $ids_inspectores[$cedula_insp];
                            $fecha = $hoja->getCell([5, $indiceFila])->getValue();
                            if($hoja->getCell([16, $indiceFila])->getValue()===null){
                                $hoja->setCellValue([16, $indiceFila], "NO");
                           }

                            $datos_array[] = array(
                                "supervisor" => $super->id,
                                'inspector' => $id_cedula,
                                'cc_operario' => $hoja->getCell([3, $indiceFila])->getValue(),
                                'municipio' => $hoja->getCell([4, $indiceFila])->getValue(),
                                'fecha_inspeccion' => $fecha,
                                'No_ACTA' => $hoja->getCell([6, $indiceFila])->getValue(),
                                'tipo_de_trabajo' => $hoja->getCell([7, $indiceFila])->getValue(),
                                'contrato' => $hoja->getCell([8, $indiceFila])->getValue(),
                                'orden_de_trabajo' => $hoja->getCell([9, $indiceFila])->getValue(),
                                'orden_externa' => $hoja->getCell([10, $indiceFila])->getValue(),
                                'categoria' => $hoja->getCell([11, $indiceFila])->getValue(),
                                'resultado' => $hoja->getCell([12, $indiceFila])->getValue(),
                                'Hora_inicio' => $hoja->getCell([13, $indiceFila])->getValue(),
                                'Hora_fin' => $hoja->getCell([14, $indiceFila])->getValue(),
                                '4_recintos' => $hoja->getCell([16, $indiceFila])->getValue(),
                                'causal' => $contenidoCelda,
                                'fecha_devolucion' => date('Y-m-d'),
                                'vence' => $vence,
                                'gestionado' => 0,
                                'dias_sin_gestion' => 0
                            );
                        } elseif ($indiceColumna === 18) {
                            $hoja->setCellValue([$indiceColumna, $indiceFila], "");
                        } elseif ($indiceColumna === 15) {
                            if ($contenidoCelda < '00:20') {
                                $celda = $hoja->getCell([$indiceColumna, $indiceFila]);
                                $celdaExcel_OK = $hoja_OK->getCell([$indiceColumna, $indiceFila_ok]);
                                $celda->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF8000');
                                $celdaExcel_OK->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF8000');
                            }
                        } else {
                         
                            $hoja->setCellValue([$indiceColumna, $indiceFila], "");
                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoCelda); 

                            $celda = $hoja->getCell([$indiceColumna, $indiceFila]);

                            $celdaExcel_OK = $hoja_OK->getCell([$indiceColumna, $indiceFila_ok]);
                            switch ($contenidoCelda) {
                                case 'COMERCIAL':
                                    $celda->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF8000');
                                    $celdaExcel_OK->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF8000');
                                    break;
                                case 'SI':
                                    $celda->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');
                                    $celdaExcel_OK->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');
                                    break;
                            }
                        }
                   
                        // Incrementar el índice de columna
                        $indiceColumna++;
                    }

                    // Incrementar el índice de fila
                    if ($validacion === 1) {
                        $indiceFila_ok++;
                    }

                    $indiceFila++;

                    $indicador_checkbox = $indicador_checkbox + 3;
                    $indicador_combobox1 = $indicador_combobox1 + 3;
                    $indicador_combobox2 = $indicador_combobox2 + 3;
                }
            }

            $hoja_OK->setCellValue([19, 2], "Comerciales");
            $hoja_OK->setCellValue([19, 3], "Residenciales");
            $hoja_OK->setCellValue([19, 4], "Vacias");
            $hoja_OK->setCellValue([19, 5], "4 recintos o mas");

            $this->contadoresHojaOK($hoja_OK);

            // Obtener las dimensiones máximas de la hoja
            $lastColumn = $hoja->getHighestColumn();
            $lastRow = $hoja->getHighestRow();

            // Construir el rango de celdas
            $range = 'A1:' . $lastColumn . $lastRow;

            // Establecer el rango de impresión de la hoja
            $hoja->getPageSetup()->setPrintArea($range);


            // Habilitar el filtro en la fila 7 hasta la columna N
            $hoja->setAutoFilter('A7:N7');
            $protection = $hoja->getProtection();
            $protection->setSheet(true); // Proteger la hoja
            $protection->setSort(true); // Permitir ordenar
            $protection->setInsertRows(true); // Permitir insertar filas
            $protection->setFormatCells(true); // Permitir formato de celdas

            $hoja->getProtection()->setAutoFilter(true); // Permitir uso de autofiltro

            // Aplicar bordes a la tabla
            $hoja->getStyle('A7:' . $hoja->getHighestColumn() . $hoja->getHighestRow())
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Ajustar automáticamente el ancho de las columnas al contenido
            foreach (range('A', $hoja->getHighestDataColumn()) as $columnID) {
                $hoja->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Aplicar bordes a la tabla
            $hoja_OK->getStyle('A2:' . $hoja_OK->getHighestColumn() . $hoja_OK->getHighestRow())
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Ajustar automáticamente el ancho de las columnas al contenido
            foreach (range('A', $hoja_OK->getHighestDataColumn()) as $columnID) {
                $hoja_OK->getColumnDimension($columnID)->setAutoSize(true);
            }


            $hoja->getStyle('A1:B5')->applyFromArray([
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                    'inside' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ]
                ],
            ]);
            $hoja->getStyle('A7:O7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('0096ff');
        }
        if ($super !== null) {

            if (!empty($datos_array_OK)) {

                foreach ($datos_array_OK as $datos_ok) {

                    try {


                        $resultado_ok = Tbl_dv_insp::where('contrato', $datos_ok['contrato'])
                            ->where('orden_trabajo', $datos_ok['orden_de_trabajo'])
                            ->get();

                        //  $resultado_ok = $validacion->getValidación_existentes();
                    } catch (\Exception $e) {
                        return response()->json(['error' => 'Error al consultar los datos en la base de datos']);
                    }

                    // BitacoraController::dd($resultado_ok[0]['FECHA_GESTION']);
                    if (!$resultado_ok->isEmpty()) {
                        foreach ($resultado_ok as $resultado) {
                            if ($resultado->fecha_gestion == null) {
                                $resultado->gestionado = 1;
                                $resultado->fecha_gestion = date('Y-m-d');
                                $resultado->save();
                            }
                        }
                    }
                }
            }
        }

        $hoja_OK->setTitle('OK');
        $totalHojas = $spreadsheet->getSheetCount();
        // Mover la hoja "OK" a la última posición
        $spreadsheet->setIndexByName('OK', $totalHojas);
        // Crear un objeto Writer para guardar la hoja de cálculo como un archivo Excel
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $rutaArchivo = str_replace(".xls", " ", session('nom_archivo'));
        $rutaArchivoFinal = str_replace("4.08", "", $rutaArchivo);
        // Guardar el archivo Excel
        $writer->save(storage_path('app/uploads/') . $rutaArchivoFinal . ".xlsx");

             

        $nombreArchivo = $rutaArchivoFinal . ".xlsx";
        if ($super !== null) {

            $usuario = Auth::user();

            $bitacora = tbl_bitacora_archivo::where('id_usuario', $usuario->id)->where('finished','=',0)->first();
           
            $bitacora->finished = 1;
            $bitacora->save();
           tbl_temp_contrato::where('id_bitacora', $bitacora->id)->delete();

            foreach ($datos_array_OK as $datos) {
                try {

                    $horaInicio = new DateTime($datos['hora_inicio']);
                    $horaFinal = new DateTime($datos['hora_fin']);

                    if ($horaFinal < $horaInicio) {
                        $horaFinal->modify('+1 day'); // Añadir un día si la hora final es menor que la hora de inicio
                    }

                    $duracion = $horaInicio->diff($horaFinal);

                    if ($datos['categoria'] === null) {
                        $consultaMovilidad = Movilidad::select('AttrCategoria')->where('NroSitio', $datos['contrato'])->where('IdTarea', $datos['no_acta'])->first();
                        $datos['categoria'] = $consultaMovilidad->AttrCategoria;
                    }

                    if ($datos['tipo_de_trabajo'] === 'SA 12164' || $datos['tipo_de_trabajo'] === 'SA 12163') {
                        $exist = tbl_bitacora_contrato::where('CONTRATO', $datos['contrato'])->where('ORDEN_TRABAJO', $datos['orden_de_trabajo'])->where('No_ACTA', $datos['no_acta'])->exists();
                        if ($exist) {
                            continue;
                        }
                    } else {

                        $exist = tbl_bitacora_contrato::where('CONTRATO', $datos['contrato'])->where('ORDEN_TRABAJO', $datos['orden_de_trabajo'])->where('TIPO_TRABAJO', $datos['tipo_de_trabajo'])->exists();
                        if ($exist) {
                            continue;
                        }
                    }
                    $contrato = new tbl_bitacora_contrato();
                    $contrato->CC_OPERARIO = $datos['cc_operario'];
                    $contrato->MUNICIPIO = $datos['municipio'];
                    $contrato->FECHA = $datos['fecha_inspeccion'];
                    $contrato->No_ACTA = $datos['no_acta'];
                    $contrato->TIPO_TRABAJO = $datos['tipo_de_trabajo'];
                    $contrato->CONTRATO = $datos['contrato'];
                    $contrato->ORDEN_TRABAJO = $datos['orden_de_trabajo'];
                    $contrato->ORDEN_EXT = $datos['orden_externa'];
                    $contrato->CATEGORIA = $datos['categoria'];
                    $contrato->RESULTADO_CIERRE = $datos['resultado'];
                    $contrato->HORA_INICIO = $datos['hora_inicio'];
                    $contrato->HORA_FINAL = $datos['hora_fin'];
                    $contrato->DURACION_INSP = $duracion->format('%H:%I');
                    $contrato->setAttribute('4_RECINTOS', $datos['4_recintos']);
                    $contrato->vence = $datos['vence'];
                    $contrato->CAUSAL_RECHAZO = $datos['rechazo'];
                    $contrato->id_bitacora = $bitacora->id;
                    $contrato->state = 1;
                    $contrato->save();
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Error al guardar los datos en la base de datos']);               
                }
            }


            if (!empty($datos_array)) {

                foreach ($datos_array as $dato) {
                    try {

                        $resultado = Tbl_dv_insp::where('contrato', $dato['contrato'])
                            ->where('orden_trabajo', $dato['orden_de_trabajo'])
                            ->get();

                        //  $resultado = $validacion->getValidación_existentes();
                    } catch (\Exception $e) {
                        return response()->json(['error' => 'Error al consultar los datos en la base de datos']);
                    }

                    if ($resultado->isEmpty()) {
                        $horaInicio = new DateTime($dato['Hora_inicio']);
                        $horaFinal = new DateTime($dato['Hora_fin']);

                        if ($horaFinal < $horaInicio) {
                            $horaFinal->modify('+1 day'); // Añadir un día si la hora final es menor que la hora de inicio
                        }

                        if ($dato['categoria'] === null) {
                            $consultaMovilidad = Movilidad::select('AttrCategoria')->where('NroSitio', $dato['contrato'])->where('IdTarea', $dato['No_ACTA'])->first();
                            $dato['categoria'] = $consultaMovilidad->AttrCategoria;
                        }

                        $duracion = $horaInicio->diff($horaFinal);

                        $guardar_dv = new Tbl_dv_insp();
                        $guardar_dv->supervisor = $dato['supervisor'];
                        $guardar_dv->inspector = $dato['inspector'];
                        $guardar_dv->CC_OPERARIO = $dato['cc_operario'];
                        $guardar_dv->municipio = $dato['municipio'];
                        $guardar_dv->fecha_insp = $dato['fecha_inspeccion'];
                        $guardar_dv->No_ACTA = $dato['No_ACTA'];
                        $guardar_dv->tipo_trabajo = $dato['tipo_de_trabajo'];
                        $guardar_dv->contrato = $dato['contrato'];
                        $guardar_dv->orden_trabajo = $dato['orden_de_trabajo'];
                        $guardar_dv->orden_ext = $dato['orden_externa'];
                        $guardar_dv->categoria = $dato['categoria'];
                        $guardar_dv->resultado_cierre = $dato['resultado'];
                        $guardar_dv->HORA_INICIO = $dato['Hora_inicio'];
                        $guardar_dv->HORA_FINAL = $dato['Hora_fin'];
                        $guardar_dv->DURACION_INSP = $duracion->format('%H:%I');
                        $guardar_dv->setAttribute('4_RECINTOS', $dato['4_recintos']);
                        $guardar_dv->causal = $dato['causal'];
                        $guardar_dv->vence = $dato['vence'];
                        $guardar_dv->fecha_dv = $dato['fecha_devolucion'];
                        $guardar_dv->gestionado = $dato['gestionado'];
                        $guardar_dv->dias_sin_gestion = $dato['dias_sin_gestion'];
                        $guardar_dv->id_bitacora = $bitacora->id;
                        $guardar_dv->activado = 1;

                        $guardar_dv->save();
                    }
                }
            }

            // Obtener los usuarios que deben recibir la notificación
            $usuarios = User::role(['admin', 'Residente', 'Coordinador_RP', 'Coordinador_RN', 'Auxiliar_coordinacion'])->get();
            $usuarioLog = Auth::user();

            // Enviar la notificación a cada usuario
            foreach ($usuarios as $usuario) {
                $usuario->notify(new Bitacora($usuarioLog->name, $bitacora->id));
            }
        }else{

            $user = Auth::user();
            $bitacora = tbl_bitacora_archivo::where('id_usuario', $user->id)->where('finished','=',0)->first();

            tbl_temp_contrato::where('id_bitacora', $bitacora->id)->delete();

            $bitacora->delete();

        }
        session()->flash('success', 'Bitacora generada correctamente');
        return response()->json([
            'ruta' => route('bitacora'),
            'nombre' => '../storage/app/uploads/' . $nombreArchivo
        ]);
    }

    public function getColorFromStyle($style)
    {

        $pattern = '/background-color:\s*rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)/';
        preg_match($pattern, $style, $matches);

        if (!empty($matches)) {


            return sprintf('%02X%02X%02X', $matches[1], $matches[2], $matches[3]);
        } else {
            return 'FFFFFF'; // Color blanco predeterminado si no se encuentra ningún color
        }
    }

    public function borrar_archivos()
    {
        $directorio = storage_path('app/uploads/');

        $archivos = array_diff(scandir($directorio), array('.', '..'));

        if (count($archivos) > 4) {

            usort($archivos, function ($a, $b) use ($directorio) {
                return filemtime("$directorio/$a") - filemtime("$directorio/$b");
            });

            // Calcular cuántos archivos se deben eliminar
            $numArchivosABorrar = count($archivos) - 4;

            for ($i = 0; $i < $numArchivosABorrar; $i++) {
                // Ruta completa del archivo a borrar
                $archivoABorrar = "$directorio/{$archivos[$i]}";

                // Verificar si es un archivo antes de intentar eliminarlo
                if (is_file($archivoABorrar)) {
                    // Borrar el archivo
                    unlink($archivoABorrar);
                }
            }
            return "Archivos depurados";
        } else {
            return "No es necesario Depurar";
        }
    }


    public function conversion_fecha($fecha_sin_formato)
    {

        $fecha_objeto = DateTime::createFromFormat('d-m-y', $fecha_sin_formato);

        if ($fecha_objeto) {
            $año = $fecha_objeto->format('y');
            $año_completo = ($año >= 50) ? "19$año" : "20$año";

            $fecha_objeto->setDate($año_completo, $fecha_objeto->format('m'), $fecha_objeto->format('d'));

            $fecha_formateada = $fecha_objeto->format('Y-m-d');
            return $fecha_formateada;
        } else {
            return null;
        }
    }

    private function contadoresHojaOK($hoja_OK)
    {

        $highestRow = $hoja_OK->getHighestDataRow();
        $contadorComerciales = 0;
        $contadorResidenciales = 0;
        $contadorVacias = 0;
        $contador4Recintos = 0;
        for ($i = 2; $i <= $highestRow; $i++) {
            $categoria = $hoja_OK->getCell([11, $i])->getValue();
            if ($categoria === "COMERCIAL") {
                $contadorComerciales++;
            } elseif ($categoria === "RESIDENCIAL") {
                $contadorResidenciales++;
            } elseif ($categoria === "") {
                $contadorVacias++;
            }
        }
        for ($i = 2; $i <= $highestRow; $i++) {
            $recintos = $hoja_OK->getCell([16, $i])->getValue();
            if ($recintos === "SI") {
                $contador4Recintos++;
            }
        }

        $hoja_OK->setCellValue([20, 2], $contadorComerciales);
        $hoja_OK->setCellValue([20, 3], $contadorResidenciales);
        $hoja_OK->setCellValue([20, 4], $contadorVacias);
        $hoja_OK->setCellValue([20, 5], $contador4Recintos);
    }

    public function devoluciones()
    {
        $devoluciones = Tbl_dv_insp::where('ACTIVADO', 1)->get();
        $gestionados = Tbl_dv_insp::where('ACTIVADO', 0)->get();

        foreach ($devoluciones as $devolucion) {
            if ($devolucion->GESTIONADO == 1) {
                $devolucion->DIAS_SIN_GESTION = 0;
                $devolucion->save();
                continue;
            }
            $fecha_devolucion = new DateTime($devolucion->FECHA_DV);
            $fecha_actual = new DateTime(date('Y-m-d'));
            $diferencia = $fecha_devolucion->diff($fecha_actual);
            $devolucion->DIAS_SIN_GESTION = $diferencia->days;
            $devolucion->save();
        }
        return view('bitacoras.devoluciones', compact('devoluciones', 'gestionados'));
    }

    public  function exportar_tabla_devoluciones(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigoHTMLdev' => 'required',
            'codigoHTMLges' => 'required'
        ], [
            'codigoHTMLdev.required' => 'Informacion de devoluciones requerida',
            'codigoHTMLges.requided' => 'Informacion de gestionados requerida'
        ]);
        try {

            $codigoHTML = $request->codigoHTMLdev . $request->codigoHTMLges;

            // Definir el patrón para encontrar las etiquetas <table> en el código HTML
            $patron = '/<table.*?>(.*?)<\/table>/s';

            preg_match_all($patron, $codigoHTML, $matches);
            // dd($matches);
            $spreadsheet = new Spreadsheet();
            foreach ($matches[0] as $indice => $tablaHTML) {
                $hoja = $spreadsheet->createSheet($indice);
                if ($indice == 0) {
                    $hoja->setTitle('Devoluciones');
                } else {
                    $hoja->setTitle('Historicos');
                }
                $dom = new DOMDocument();
                $dom->loadHTML($tablaHTML);

                $filas = $dom->getElementsByTagName('tr');

                $indiceFila = 1;

                foreach ($filas as $fila) {
                    // Obtener todas las celdas de la fila
                    $celdas = $fila->getElementsByTagName('td');

                    $encabezados = $fila->getElementsByTagName('th');

                    // Si hay celdas de encabezado, procesarlas
                    if ($encabezados->length > 0) {
                        $indiceColumna = 1;
                        foreach ($encabezados as $encabezado) {
                            // Obtener el contenido del encabezado
                            $contenidoEncabezado = $encabezado->nodeValue;

                            // Pegar el contenido del encabezado en la hoja de cálculo
                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoEncabezado);

                            // Incrementar el índice de columna
                            $indiceColumna++;
                        }
                        // Incrementar el índice de fila
                        $indiceFila++;
                    }

                    // Si hay celdas de datos, procesarlas
                    if ($celdas->length > 0) {
                        // Inicializar el índice de columna en 1
                        $indiceColumna = 1;

                        foreach ($celdas as $celda) {

                            $estiloCelda = $celda->getAttribute('style');

                            $contenidoCelda = $celda->nodeValue;

                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoCelda);
                            $celda = $hoja->getCell([$indiceColumna, $indiceFila]);

                            if (!empty($estiloCelda)) {
                                $celda->getStyle()->applyFromArray([
                                    'fill' => [
                                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => $this->getColorFromStyle($estiloCelda)],
                                    ],
                                ]);
                            }

                            // Incrementar el índice de columna
                            $indiceColumna++;
                        }
                        $indiceFila++;
                    }
                }
                // Aplicar bordes a la tabla
                $hoja->getStyle('A1:' . $hoja->getHighestColumn() . $hoja->getHighestRow())
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Ajustar automáticamente el ancho de las columnas al contenido
                foreach (range('A', $hoja->getHighestDataColumn()) as $columnID) {
                    $hoja->getColumnDimension($columnID)->setAutoSize(true);
                }

                $hoja->getStyle('A1:M1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('0096ff');
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');;

            $fecha_actual = date('Y-m-d');

            $writer->save(storage_path('app/uploads/') . "Devoluciones " . $fecha_actual . ".xlsx");

            $nombreArchivo = "Devoluciones " . $fecha_actual . ".xlsx";


            header('Content-Type: application/json');
            return response()->json([
                'nombreArchivo' => $nombreArchivo,
                'ruta' => '../storage/app/uploads/'
            ]);
        } catch (\Exception $e) {

            http_response_code(500);
        }
    }


    public function reportes()
    {
        $bitacoras = tbl_bitacora_archivo::where('finished','=','1')->get()->map(function ($bitacora) {
            $bitacora->fecha_creacion = $bitacora->created_at->format('Y-m-d');
            return $bitacora;
        });
        return view('bitacoras.reportes', compact('bitacoras'));
    }

    public function verReporte($id_bitacora)
    {
        $bitacora = tbl_bitacora_archivo::find($id_bitacora);
        $causales_dv = tbl_bitacoras_causal::all();
    
        if ($bitacora == null) {
            return redirect()->route('bitacoras.reportes')->with('error', 'Bitacora no encontrada');
        }
        return view('bitacoras.verReporte', compact('bitacora','causales_dv'));
    }

    public function consultaReporte($id_bitacora)
    {
        //contratos asignados a la bitacora
        $contratos = tbl_bitacora_contrato::selectRaw("CONCAT(tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS nombre_completo, tbl_bitacora_contratos.id,tbl_bitacora_contratos.CC_OPERARIO, tbl_bitacora_contratos.MUNICIPIO, tbl_bitacora_contratos.FECHA, tbl_bitacora_contratos.No_ACTA, tbl_bitacora_contratos.TIPO_TRABAJO, tbl_bitacora_contratos.CONTRATO, tbl_bitacora_contratos.ORDEN_TRABAJO, tbl_bitacora_contratos.ORDEN_EXT, tbl_bitacora_contratos.CATEGORIA, tbl_bitacora_contratos.RESULTADO_CIERRE, tbl_bitacora_contratos.HORA_INICIO, tbl_bitacora_contratos.HORA_FINAL, tbl_bitacora_contratos.DURACION_INSP,
        tbl_bitacora_contratos.`vence`,tbl_bitacora_contratos.`CAUSAL_RECHAZO`")
            ->join('tbl_insp_cali', 'tbl_insp_cali.cedula', '=', 'tbl_bitacora_contratos.CC_OPERARIO')
            ->where('tbl_bitacora_contratos.id_bitacora', $id_bitacora)
            ->get();

        return response()->json(['contratos' => $contratos]);
    }

    public function consultaIndicadores($id_bitacora)
    {
        //contadores de cierres
        $certificadas = tbl_bitacora_contrato::where('id_bitacora', $id_bitacora)->where('RESULTADO_CIERRE', 'CERTIFICADA')->count();
        $certificadasConNovedades = tbl_bitacora_contrato::where('id_bitacora', $id_bitacora)->where('RESULTADO_CIERRE', 'CERTIFICADA CON NOVEDADES')->count();
        $inspeccionadasConDefectoCritico = tbl_bitacora_contrato::where('id_bitacora', $id_bitacora)->where('RESULTADO_CIERRE', 'INSPECCIONADA CON DEFECTO CRITICO VALLE')->count();
        $inspeccionadasConDefectoNoCritico = tbl_bitacora_contrato::where('id_bitacora', $id_bitacora)->where('RESULTADO_CIERRE', 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE')->count();
        $totalContratosOK = tbl_bitacora_contrato::where('id_bitacora', $id_bitacora)->count();
        return response()->json([
            'certificadas' => $certificadas,
            'certificadasConNovedades' => $certificadasConNovedades,
            'inspeccionadasConDefectoCritico' => $inspeccionadasConDefectoCritico,
            'inspeccionadasConDefectoNoCritico' => $inspeccionadasConDefectoNoCritico,
            'totalContratosOK' => $totalContratosOK
        ]);
    }

    public function actualizar_devolucion($id)
    {

        $devolucion = tbl_dv_insp::find($id);
        $devolucion->GESTIONADO = 1;
        $devolucion->FECHA_GESTION = date('Y-m-d');
        $devolucion->save();
       /*  $exist = tbl_bitacora_contrato::where('CONTRATO', $devolucion->CONTRATO)->where('ORDEN_TRABAJO', $devolucion->ORDEN_TRABAJO)->exists();
        if ($exist) {
            // Obtener los usuarios que deben recibir la notificación
            $usuarios = User::role(['admin', 'Residente', 'Coordinador_RP', 'Coordinador_RN'])->get();
            $usuarioLog = Auth::user();
            // Enviar la notificación a cada usuario
            foreach ($usuarios as $usuario) {
                $usuario->notify(new Mod_Devolucion($usuarioLog->name, $devolucion->CONTRATO, $devolucion->id_bitacora));
            }
            return redirect()->route('bitacora.devoluciones');
        } */

        /*  $contrato = new tbl_bitacora_contrato();
        $contrato->CC_OPERARIO = $devolucion->CC_OPERARIO;
        $contrato->MUNICIPIO = $devolucion->MUNICIPIO;
        $contrato->FECHA = $devolucion->FECHA_INSP;
        $contrato->No_ACTA = $devolucion->No_ACTA;
        $contrato->TIPO_TRABAJO = $devolucion->TIPO_TRABAJO;
        $contrato->CONTRATO = $devolucion->CONTRATO;
        $contrato->ORDEN_TRABAJO = $devolucion->ORDEN_TRABAJO;
        $contrato->ORDEN_EXT = $devolucion->ORDEN_EXT;
        $contrato->CATEGORIA = $devolucion->CATEGORIA;
        $contrato->RESULTADO_CIERRE = $devolucion->RESULTADO_CIERRE;
        $contrato->HORA_INICIO = $devolucion->HORA_INICIO;
        $contrato->HORA_FINAL = $devolucion->HORA_FINAL;
        $contrato->DURACION_INSP = $devolucion->DURACION_INSP;
        $contrato->setAttribute('4_RECINTOS', $devolucion->getAttribute('4_RECINTOS'));
        $contrato->id_bitacora = $devolucion->id_bitacora;
        $contrato->vence = $devolucion->vence;
        $contrato->state = 1;
        $contrato->save();
 */
      /*   // Obtener los usuarios que deben recibir la notificación
        $usuarios = User::role(['admin', 'Residente', 'Coordinador_RP', 'Coordinador_RN', 'Auxiliar_coordinacion'])->get();
        $usuarioLog = Auth::user();

        // Enviar la notificación a cada usuario
        foreach ($usuarios as $usuario) {
            $usuario->notify(new Mod_Devolucion($usuarioLog->name, $devolucion->CONTRATO, $devolucion->id_bitacora));
        } */
        return redirect()->route('bitacora.devoluciones');
    }

    public function buscarPorContrato(Request $request)
    {

        $contrato = $request->input('contrato');

        $bitacoras = tbl_bitacora_archivo::whereIn(
            'id',
            tbl_bitacora_contrato::select('id_bitacora')
                ->where('CONTRATO', 'LIKE', '%' . $contrato . '%')
        )->get();


        // Devolver resultados en formato JSON
        return response()->json($bitacoras);
    }

    public function getMunicipiosJson(Request $request)
    {
        $term = $request->input('term');

        $municipios = tbl_localidades_municipio::where('nombre', 'like', "%$term%")
            ->pluck('nombre', 'nombre'); // Obtener nombre e ID

        return response()->json($municipios);
    }

    public function download($nombreArchivo)
    {
        $rutaCompleta = storage_path('app/uploads/') . $nombreArchivo;

        // Verificar si el archivo existe
        if (Storage::exists('uploads/' . $nombreArchivo)) {
            return response()->download($rutaCompleta);
        } else {
            // El archivo no existe, manejar el error
            return redirect()->route('bitacoras.reportes')->with('error', 'Archivo no encontrado');
        }
    }

    public function devolver(Request $request, $ids, $bitacora)
    {
        $idsArray = explode(',', $ids);
        $archivo = tbl_bitacora_archivo::find($bitacora);
   
        // Validar y sanitizar los IDs (como en el ejemplo anterior)
        try {
            $contratos = tbl_bitacora_contrato::findMany($idsArray);
         
            foreach ($contratos as $contrato) {
                $inspector = tbl_insp_cali::where('cedula', $contrato->CC_OPERARIO)->first();
                $devolucion = new tbl_dv_insp();
                $devolucion->supervisor = $archivo->id_usuario;
                $devolucion->inspector = $inspector->id;
                $devolucion->CC_OPERARIO = $contrato->CC_OPERARIO;
                $devolucion->municipio = $contrato->MUNICIPIO;
                $devolucion->fecha_insp = $contrato->FECHA;
                $devolucion->No_ACTA = $contrato->No_ACTA;
                $devolucion->tipo_trabajo = $contrato->TIPO_TRABAJO;
                $devolucion->contrato = $contrato->CONTRATO;
                $devolucion->orden_trabajo = $contrato->ORDEN_TRABAJO;
                $devolucion->orden_ext = $contrato->ORDEN_EXT;
                $devolucion->categoria = $contrato->CATEGORIA;
                $devolucion->resultado_cierre = $contrato->RESULTADO_CIERRE;
                $devolucion->HORA_INICIO = $contrato->HORA_INICIO;
                $devolucion->HORA_FINAL = $contrato->HORA_FINAL;
                $devolucion->DURACION_INSP = $contrato->DURACION_INSP;
                $devolucion->setAttribute('4_RECINTOS', $contrato->getAttribute('4_RECINTOS'));
                $devolucion->vence = $contrato->vence;
                $devolucion->fecha_dv = date('Y-m-d');
                $devolucion->gestionado = 0;
                $devolucion->dias_sin_gestion = 0;
                $devolucion->id_bitacora = $bitacora;
                $devolucion->activado = 1;
                $devolucion->diseno_especial = 0;
                $devolucion->CAUSAL = $request->input('causal');
                $devolucion->save();
                $contrato->delete();
            }

            $userLog = Auth::user();
            $super = User::find($archivo->id_usuario);

            $super->notify(new devolucion($userLog->name, $devolucion->contrato, $super->name, $archivo, $request->input('causal')));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al devolver los contratos '.$e->getMessage()]);
        }
        return response()->json($contratos);
    }
}
