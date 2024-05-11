<?php

namespace App\Http\Controllers;

use App\Models\tbl_dv_insp;
use App\Models\User;
use App\Models\Tbl_insp_cali;
use DateTime;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\tbl_localidades_municipio;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class BitacoraController extends Controller
{
    public function ver()
    {
        $supervisores = Auth::user();
        if ($supervisores->hasRole('Supervisor')) {
            return view('bitacoras.generar', compact('supervisores'));
        }
        $supervisores = User::role('Supervisor')->get();
        return view('bitacoras.generar', compact('supervisores'));
    }

    public function generar_bitacora(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supervisor' => 'required',
            'archivo' => 'required|mimes:xls,xlsx'
        ], [
            'supervisor.required' => 'Por favor seleccione un supervisor',
            'archivo.required' => 'Por favor seleccione un archivo',
            'archivo.mimes' => 'El archivo seleccionado no es válido'
        ]);

        if ($validator->fails()) {
            return redirect()->route('bitacora')->withErrors($validator)->withInput()->with('error', $validator->errors()->first());
        }

        $supervisor = User::find($request->supervisor);

        $nombreArchivo = $request->archivo->getClientOriginalName() . $supervisor->name . ".xls";

        $request->archivo->storeAs('uploads', $nombreArchivo);

        $rutaDestino = storage_path('app/uploads/') . $nombreArchivo;

        $excelFilePath = $rutaDestino;

        return $this->procesarArchivoExcel($supervisor->name, $supervisor->id, $excelFilePath);
    }

    public function procesarArchivoExcel($nom_super, $id_super, $excelFilePath)
    {

        session(['nom_archivo' => basename($excelFilePath)]);
        session(['super' => $nom_super]);
        //consultas a la base de datos
        $inspectores = Tbl_insp_cali::where('SUPERVISOR', $id_super)
            ->where('state', 1)
            ->get();

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

        return view('bitacoras.tabla', compact('nombres', 'spreadsheet', 'id_super', 'municipios', 'inspectores'));
    }

    public function guardar_tabla(Request $request, User $super)
    {

        $encabezados = $request->encabezado;

        $dataTable = $request->datos;
        $codigoHTML_tabla_indicadores = $request->codigoHTML_tabla_indicadores;
        $valoresSeleccionados = $request->valoresSeleccionados;
        // Definir el patrón para encontrar las etiquetas <table> en el código HTML
        $patron = '/<table.*?>(.*?)<\/table>/s';

        $datos_array = array();

        // Encontrar todas las coincidencias de las etiquetas <table> en el código HTML
        //preg_match_all($patron, $codigoHTML, $matches);
        preg_match_all($patron, $codigoHTML_tabla_indicadores, $datos);


        // Crear una instancia de la clase Spreadsheet
        $spreadsheet = new Spreadsheet();
        $hoja_OK = $spreadsheet->getSheetByName('Worksheet');
        $indiceFila_ok = 2;
        foreach ($dataTable as $indice => $tabla) {

            $idTabla = "$indice";


            $nombre_tabla = $tabla[0][0] ?? "Tabla $indice";

            $nombre_tabla = strlen($nombre_tabla) > 31 ? substr($nombre_tabla, 0, 31) : $nombre_tabla;

            // Crear una nueva hoja de cálculo para esta tabla
            $hoja = $spreadsheet->createSheet($indice);
            $hoja->setTitle($nombre_tabla);


            if (array_key_exists($indice, $datos[0])) {
                $datosTabla = $datos[0][$indice];
            }
            $dom_tbl_indicadores = new DOMDocument();
            $dom_tbl_indicadores->loadHTML($datosTabla);
            $filas_tbl_indicadores = $dom_tbl_indicadores->getElementsByTagName('tr');


            $indiceFila_tbl_ind = 0;
            foreach ($filas_tbl_indicadores as $indicador) {
                $indiceColumna_tbl_ind = 1;
                $datos_indicadores = $indicador->getElementsByTagName('td');

                foreach ($datos_indicadores as $dato) {
                    $contenidodato = $dato->nodeValue;

                    $hoja->setCellValue([$indiceColumna_tbl_ind, $indiceFila_tbl_ind], $contenidodato);
                    $indiceColumna_tbl_ind++;
                }

                $indiceFila_tbl_ind++;
            }

            // Inicializar el índice de fila en 1

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


                        // Obtener el identificador único del combobox y checkbox
                        $idCheckbox = $indicador_checkbox;
                        $idCombobox1 = $indicador_combobox1;
                        $idCombobox2 = $indicador_combobox2;

                        // Obtener la clave para buscar en los valores seleccionados
                        $claveCheckbox = "select_$idTabla" . "_$idCheckbox";
                        $clave = "select_$idTabla" . "_$idCombobox1";
                        $clave2 = "select_$idTabla" . "_$idCombobox2";
                        if ($indiceColumna === 15 && isset($valoresSeleccionados[$claveCheckbox])) {
                            $contenidoCelda = $valoresSeleccionados[$claveCheckbox];
                            if ($contenidoCelda === "true") {
                                $contenidoCelda = "SI";
                                $celda_color = $hoja->getCell([$indiceColumna, $indiceFila]);
                                $celda_color->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF8000');
                            } else {
                                $contenidoCelda = "NO";
                            }
                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoCelda);
                        }
                        if (array_key_exists($clave, $valoresSeleccionados) && $valoresSeleccionados[$clave] === "OK") {
                            $validacion = 0;
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
                        if ($indiceColumna === 16 && isset($valoresSeleccionados[$clave])) {
                            // Usar el valor seleccionado en lugar del contenido de la celda
                            $contenidoCelda = $valoresSeleccionados[$clave];
                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoCelda);
                            if ($valoresSeleccionados[$clave] === "OK") {
                                $celda_color = $hoja->getCell([7, $indiceFila]);
                                $celda_color->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');
                                $cedula_insp = $hoja->getCell([2, $indiceFila])->getValue();
                                // guardar un array con todos los contratos en ok
                                $ids_inspectores = session('ids_inspectores');
                                $id_cedula = $ids_inspectores[$cedula_insp];
                                $fecha = $hoja->getCell([4, $indiceFila])->getValue();
                                $fecha_formateada = $this->conversion_fecha($fecha);
                                
                                $datos_array_OK[] = array(
                                    'inspector' => $id_cedula,
                                    'fecha_inspeccion' => $fecha_formateada,
                                    'tipo_de_trabajo' => $hoja->getCell([6, $indiceFila])->getValue(),
                                    'contrato' => $hoja->getCell([7, $indiceFila])->getValue(),
                                    'orden_de_trabajo' => $hoja->getCell([8, $indiceFila])->getValue(),
                                    'orden_externa' => $hoja->getCell([9, $indiceFila])->getValue(),
                                    'categoria' => $hoja->getCell([10, $indiceFila])->getValue(),
                                    'resultado' => $hoja->getCell([11, $indiceFila])->getValue(),
                                    'hora_inicio' => $hoja->getCell([12, $indiceFila])->getValue(),
                                    'hora_fin' => $hoja->getCell([13, $indiceFila])->getValue(),
                                    'duracion' => $hoja->getCell([14, $indiceFila])->getValue(),
                                );
                            } else {

                                $celda_color = $hoja->getCell([7, $indiceFila]);
                                $celda_color->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
                            }
                        } elseif (array_key_exists($clave, $valoresSeleccionados) && $valoresSeleccionados[$clave] === "DV" && $indiceColumna === 17 && isset($valoresSeleccionados[$clave2])) {
                            if ($valoresSeleccionados[$clave2] === '--SELECCIONE CAUSAL--') {
                                header('Content-Type: application/json');
                                echo json_encode(['error' => 'Por favor, seleccione una causal para los contratos en estado de devolucion']);
                                exit;
                            }
                            $contenidoCelda = $valoresSeleccionados[$clave2];
                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoCelda);

                            $cedula_insp = $hoja->getCell([2, $indiceFila])->getValue();
                            $ids_inspectores = session('ids_inspectores');
                            $id_cedula = $ids_inspectores[$cedula_insp];
                            $fecha = $hoja->getCell([4, $indiceFila])->getValue();
                            $fecha_formateada = $this->conversion_fecha($fecha);

                            $datos_array[] = array(
                                "supervisor" => $super->id,
                                'inspector' => $id_cedula,
                                'fecha_inspeccion' => $fecha_formateada,
                                'tipo_de_trabajo' => $hoja->getCell([6, $indiceFila])->getValue(),
                                'contrato' => $hoja->getCell([7, $indiceFila])->getValue(),
                                'orden_de_trabajo' => $hoja->getCell([8, $indiceFila])->getValue(),
                                'orden_externa' => $hoja->getCell([9, $indiceFila])->getValue(),
                                'resultado' => $hoja->getCell([11, $indiceFila])->getValue(),
                                'causal' => $contenidoCelda,
                                'fecha_devolucion' => date('Y-m-d'),
                                'gestionado' => 0,
                                'dias_sin_gestion' => 0
                            );
                        } elseif ($indiceColumna === 17) {
                            $hoja->setCellValue([$indiceColumna, $indiceFila], "");
                        } elseif ($indiceColumna === 14) {
                            if($contenidoCelda < '00:20'){
                                $celda = $hoja->getCell([$indiceColumna, $indiceFila]);
                                $celdaExcel_OK = $hoja_OK->getCell([$indiceColumna, $indiceFila_ok]);
                                $celda->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF8000');
                                $celdaExcel_OK->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF8000');
                            }
                        } else {
                            $hoja->setCellValue([$indiceColumna, $indiceFila], $contenidoCelda);
        
                            $celda = $hoja->getCell([$indiceColumna, $indiceFila]);

                            $celdaExcel_OK = $hoja_OK->getCell([$indiceColumna, $indiceFila_ok]);
                            switch($contenidoCelda){
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

            $hoja_OK->setCellValue([17, 2], "Comerciales");
            $hoja_OK->setCellValue([17, 3], "Residenciales");
            $hoja_OK->setCellValue([17, 4], "Vacias");
            $hoja_OK->setCellValue([17, 5], "4 recintos o mas");

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
        if (!empty($datos_array_OK)) {
           
            foreach ($datos_array_OK as $datos_ok) {

                try {
                    $resultado_ok = Tbl_dv_insp::where('contrato', $datos_ok['contrato'])
                        ->where('orden_trabajo', $datos_ok['orden_de_trabajo'])
                        ->get();

                    //  $resultado_ok = $validacion->getValidación_existentes();
                } catch (\Exception $e) {
                    throw new \Exception("Error al consultar los datos en la base de datos");
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

        if (!empty($datos_array)) {

            foreach ($datos_array as $dato) {
                try {

                    $resultado = Tbl_dv_insp::where('contrato', $dato['contrato'])
                        ->where('orden_trabajo', $dato['orden_de_trabajo'])
                        ->get();

                    //  $resultado = $validacion->getValidación_existentes();
                } catch (\Exception $e) {
                    throw new \Exception("Error al consultar los datos en la base de datos");
                }

                if ($resultado->isEmpty()) {

                    $guardar_dv = new Tbl_dv_insp();
                    $guardar_dv->supervisor = $dato['supervisor'];
                    $guardar_dv->inspector = $dato['inspector'];
                    $guardar_dv->fecha_insp = $dato['fecha_inspeccion'];
                    $guardar_dv->tipo_trabajo = $dato['tipo_de_trabajo'];
                    $guardar_dv->contrato = $dato['contrato'];
                    $guardar_dv->orden_trabajo = $dato['orden_de_trabajo'];
                    $guardar_dv->orden_ext = $dato['orden_externa'];
                    $guardar_dv->resultado_cierre = $dato['resultado'];
                    $guardar_dv->causal = $dato['causal'];
                    $guardar_dv->fecha_dv = $dato['fecha_devolucion'];
                    $guardar_dv->gestionado = $dato['gestionado'];
                    $guardar_dv->dias_sin_gestion = $dato['dias_sin_gestion'];
                    $guardar_dv->activado = 1;

                    $guardar_dv->save();
                }
            }
        }
        $hoja_OK->setTitle('OK');
        $totalHojas = $spreadsheet->getSheetCount();
        // Mover la hoja "OK" a la última posición
        $spreadsheet->setIndexByName('OK', $totalHojas);
        // Crear un objeto Writer para guardar la hoja de cálculo como un archivo Excel
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $rutaArchivoFinal = str_replace(".xls", " ", session('nom_archivo'));
        // Guardar el archivo Excel
        $writer->save(storage_path('app/uploads/') . $rutaArchivoFinal . ".xlsx");

        $nombreArchivo = $rutaArchivoFinal . ".xlsx";

        header('Content-Type: application/json');
        return response()->json([
            'nombreArchivo' => $nombreArchivo,
            'ruta' => 'storage/app/uploads/'
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
            $categoria = $hoja_OK->getCell([10, $i])->getValue();
            if ($categoria === "COMERCIAL") {
                $contadorComerciales++;
            } elseif ($categoria === "RESIDENCIAL") {
                $contadorResidenciales++;
            } elseif ($categoria === "") {
                $contadorVacias++;
            }
        }
        for ($i = 2; $i <= $highestRow; $i++) {
            $recintos = $hoja_OK->getCell([15, $i])->getValue();
            if ($recintos === "SI") {
                $contador4Recintos++;
            }
        }

        $hoja_OK->setCellValue([18, 2], $contadorComerciales);
        $hoja_OK->setCellValue([18, 3], $contadorResidenciales);
        $hoja_OK->setCellValue([18, 4], $contadorVacias);
        $hoja_OK->setCellValue([18, 5], $contador4Recintos);
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
}
