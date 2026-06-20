<?php

namespace App\Services\PQRS;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\tbl_insp_cali;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class CoordinacionPQRSLectorHTML
{


    private static function extraerDatosDesdeHtml($fechaAsignacion, $codUnidad, $numeroOrden)
    {
        // Arreglo base por si no se encuentra la información
        $datosVacios = [
            'Medidor' => 'No encontrado',
            'Cuadrilla' => 'No encontrado',
            'Localidad_Servicio' => 'No encontrado',
            'Direccion_Barrio' => 'No encontrado',
            'Solicitud_Requerida' => 'No encontrado',
            'Nombre_Cliente' => 'No encontrado',
            'Categoria' => 'No encontrado',
            'Subcategoria' => 'No encontrado',
            'Nombre_Solicitante' => 'No encontrado',
            'Telefono_Contacto' => 'No encontrado',
            'Estado' => 'Archivo u Orden no encontrada'
        ];

        // 1. Convertir la fecha inicial a un objeto Carbon
        try {
            $fechaLimpia = explode(' ', trim($fechaAsignacion))[0];
            if (strpos($fechaLimpia, '/') !== false) {
                $fechaCarbon = Carbon::createFromFormat('d/m/Y', $fechaLimpia);
            } else {
                $fechaCarbon = Carbon::parse($fechaLimpia);
            }
        } catch (\Exception $e) {
            $fechaCarbon = Carbon::parse($fechaAsignacion);
        }

        // LIMITE DE BÚSQUEDA
        $diasMaximosBusqueda = 10;

        for ($i = 0; $i <= $diasMaximosBusqueda; $i++) {
            $fechaActualBusqueda = $fechaCarbon->copy()->addDays($i)->format('Y-m-d');
            $nombreArchivo = "{$fechaActualBusqueda} {$codUnidad}.html";
            $rutaArchivo = "PQRS_HTML/{$nombreArchivo}";

            if (Storage::exists($rutaArchivo)) {
                $htmlCompleto = Storage::get($rutaArchivo);
                $bloques = explode('<HTML>', $htmlCompleto);

                foreach ($bloques as $bloque) {
                    if (strpos($bloque, $numeroOrden) !== false) {

                        // --- TRUCO MAGICO: Limpiamos todo el HTML y lo volvemos texto plano ---
                        // Reemplazamos cierres para evitar que las palabras se peguen
                        $textoLimpio = str_replace(['</td>', '</span>', '</p>', '</tr>'], ' ', $bloque);
                        $textoLimpio = strip_tags($textoLimpio); // Quitamos etiquetas
                        $textoLimpio = html_entity_decode($textoLimpio, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Traducimos tildes HTML
                        $textoLimpio = preg_replace('/\s+/', ' ', $textoLimpio); // Normalizamos espacios extra

                        // Definimos los límites de búsqueda en el texto plano
                        $patrones = [
                            'Cuadrilla'           => '/Cuadrilla:\s*(.*?)\s*Localidad del Servicio:/iu',
                            'Localidad_Servicio'  => '/Localidad del Servicio:\s*(.*?)\s*No Orden de Trabajo:/iu',
                            'Direccion_Barrio'    => '/Dirección y barrio del servicio:\s*(.*?)\s*Solicitud Requerida:/iu',
                            'Solicitud_Requerida' => '/Solicitud Requerida:\s*(.*?)\s*Nombre del Cliente:/iu',
                            'Nombre_Cliente'      => '/Nombre del Cliente:\s*(.*?)\s*Categoria:/iu',
                            'Categoria'           => '/Categoria:\s*(.*?)\s*Número Contrato:/iu',
                            'Subcategoria'        => '/Subcategoria:\s*(.*?)\s*Nombre del Solicitante:/iu',
                            'Nombre_Solicitante'  => '/Nombre del Solicitante:\s*(.*?)\s*Teléfono Contacto/iu',
                            'Telefono_Contacto'   => '/Teléfono Contacto\s*:\s*(.*?)\s*Número\s+Medidor/iu',
                            'Medidor'             => '/Número\s+Medidor\s*(.*?)\s*Observaciones/iu'
                        ];

                        $resultado = [];
                        foreach ($patrones as $llave => $patron) {
                            if (preg_match($patron, $textoLimpio, $coincidencias)) {
                                $resultado[$llave] = trim($coincidencias[1]);
                            } else {
                                $resultado[$llave] = 'No detectado';
                            }
                        }

                        $resultado['Estado'] = 'Encontrado';
                        return $resultado;
                    }
                }
            }
        }

        $datosVacios['Estado'] = "No encontrado tras buscar {$diasMaximosBusqueda} días.";
        return $datosVacios;
    }

    public static function CrearArchivos($datosGDW)
    {
        // 1. Preparamos los arreglos donde guardaremos las filas de ambos reportes
        $filasPuntoInteres = [];
        $filasTareas = [];

        // --- FUNCIÓN PARA LIMPIAR TEXTOS (Evita celdas altas y espacios dobles) ---
        $limpiarTexto = function($texto) {
            if (empty($texto)) return '';
            return trim(preg_replace('/\s+/', ' ', $texto));
        };

        // OPTIMIZACIÓN: Traemos todas las cédulas de los inspectores de una vez
        $inspectores = tbl_insp_cali::pluck('cedula', 'id')->toArray();

        // Fechas actuales fijas para el archivo de Tareas
        $fechaActual = Carbon::now()->format('d/m/Y');
        $fechaVisita = $fechaActual . ' 7:00';
        $fechaFinProgramado = $fechaActual . ' 17:00';

        foreach ($datosGDW as $queja) {
            if (!empty($queja->FECHA_ASIGNACION) && !empty($queja->COD_UNIDAD_OPER) && !empty($queja->NUMERO_ORDEN)) {

                // Extraemos los datos del HTML
                $datosExtraidos = self::extraerDatosDesdeHtml(
                    $queja->FECHA_ASIGNACION,
                    $queja->COD_UNIDAD_OPER,
                    $queja->NUMERO_ORDEN
                );

                // --- PREPARACIÓN DE DATOS COMUNES ---
                $observacionOriginal = $limpiarTexto($queja->OBSERVACION_SOLICITUD);
                $contratoFormateado = '::' . ($queja->CONTRATO ?? '');
                $direccionLimpia = $limpiarTexto($queja->DIRECCION);
                $clienteLimpio = $limpiarTexto($queja->NOMBRE);

                // --- LÓGICA PARA PUNTO DE INTERÉS ---
                $obsChunks = str_split($observacionOriginal, 99);
                $fechaLimite = $queja->FECHA_LIMITE ? Carbon::parse($queja->FECHA_LIMITE)->format('d/m/Y') : '';

                $filasPuntoInteres[] = [
                    'Direccion' => $direccionLimpia ?? '',
                    'Departamento' => $queja->DESC_DEPART ?? '',
                    'Localidad' => $queja->DESC_LOCALIDAD ?? '',
                    'Cliente' => $clienteLimpio ?? '',
                    'Contrato' => $contratoFormateado,
                    'Contacto' => $datosExtraidos['Telefono_Contacto'] !== 'No detectado' ? $datosExtraidos['Telefono_Contacto'] : '',
                    'EMAIL' => '',
                    'EMAIL CC' => '',
                    'Telefono movil' => '',
                    'LATITUD' => '',
                    'LONGITUD' => '',
                    'idCliente' => '137776',
                    'Fecha limite Legalizacion' => $fechaLimite,
                    'Fecha orden de trabajo' => $queja->FECHA_ASIGNACION ?? '',
                    'Cuadrilla' => $datosExtraidos['Cuadrilla'],
                    'Localidad del servicio' => $datosExtraidos['Localidad_Servicio'],
                    'Orden' => $queja->NUMERO_ORDEN ?? '',
                    'Direccion y barrio del servicio' => $datosExtraidos['Direccion_Barrio'],
                    'Solicitud requerida' => $datosExtraidos['Solicitud_Requerida'],
                    'Nombre cliente' => $datosExtraidos['Nombre_Cliente'],
                    'Categoria' => $datosExtraidos['Categoria'],
                    'Subcategoria' => $datosExtraidos['Subcategoria'],
                    'Nombre solicitante' => $datosExtraidos['Nombre_Solicitante'],
                    'Telefono contacto' => $datosExtraidos['Telefono_Contacto'],
                    'Numero medidor' => $datosExtraidos['Medidor'],
                    'Observaciones del registro.1' => $obsChunks[0] ?? '',
                    'Observaciones del registro.2' => $obsChunks[1] ?? '',
                    'Observaciones del registro.3' => $obsChunks[2] ?? '',
                    'Observaciones del registro.4' => $obsChunks[3] ?? ''
                ];

                // --- LÓGICA PARA TAREAS ---
                $cedulaTecnico = '';
                if (!empty($queja->ASIGNADO)) {
                    $partesAsignado = explode('.', $queja->ASIGNADO);
                    $idTecnico = trim($partesAsignado[0]);
                    $cedulaTecnico = $inspectores[$idTecnico] ?? '';
                }

                $filasTareas[] = [
                    'Nro contrato' => $contratoFormateado,
                    'Direccion' => $direccionLimpia ?? '',
                    'fecha Visita' => $fechaVisita,
                    'fecha Fin programado' => $fechaFinProgramado,
                    'Grupo' => 'INSP-VALLE',
                    'Nro Operario' => $cedulaTecnico,
                    'Id Tipo de Tarea' => '37179',
                    'Id Prioridad' => '1680',
                    'Detalle' => 'Observaciones del registro:' . $observacionOriginal,
                    'Nro de tarea interno' => '',
                    'Codigo del bien (opcional)' => ''
                ];
            }
        }

        // 2. CREACIÓN DE LOS ARCHIVOS (XLSX y CSV)
        $timestamp = Carbon::now()->format('Ymd_His');

        // Asegurarnos de que el directorio 'uploads' exista
        $directorioUploads = storage_path('app/uploads');
        if (!File::exists($directorioUploads)) {
            File::makeDirectory($directorioUploads, 0755, true);
        }

        // --- [NUEVO] Generar Excel (.xlsx) para PUNTO DE INTERÉS ---
        $nombrePuntoInteres = 'PUNTO_de_interes_' . $timestamp . '.xlsx';
        $rutaPuntoInteres = $directorioUploads . DIRECTORY_SEPARATOR . $nombrePuntoInteres;

        if (!empty($filasPuntoInteres)) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Escribir cabeceras y datos
            $sheet->fromArray(array_keys($filasPuntoInteres[0]), NULL, 'A1');
            $sheet->fromArray($filasPuntoInteres, NULL, 'A2');

            $writer = new Xlsx($spreadsheet);
            $writer->save($rutaPuntoInteres);

            // Liberar memoria inmediatamente
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $writer);
        }

        // --- [MANTENIDO] Generar CSV (.csv) para TAREAS ---
        $nombreTareas = 'Tareas_' . $timestamp . '.csv';
        $rutaTareas = $directorioUploads . DIRECTORY_SEPARATOR . $nombreTareas;

        $file2 = fopen($rutaTareas, 'w');
        fputs($file2, "\xEF\xBB\xBF"); // Forzar UTF-8 con BOM
        if (!empty($filasTareas)) {
            fputcsv($file2, array_keys($filasTareas[0]), ';');
            foreach ($filasTareas as $fila) {
                fputcsv($file2, $fila, ';');
            }
        }
        fclose($file2);

        // --- Retornar las URLs Firmadas correspondientes ---
        return [
            'success' => true,
            'mensaje' => 'Archivos generados correctamente (Punto de Interés en XLSX y Tareas en CSV).',
            'url_punto_interes' => URL::signedRoute('descargar.archivo', ['file' => $nombrePuntoInteres]),
            'url_tareas' => URL::signedRoute('descargar.archivo', ['file' => $nombreTareas])
        ];
    }
}
