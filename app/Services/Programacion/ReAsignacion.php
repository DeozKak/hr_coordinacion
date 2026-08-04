<?php

namespace App\Services\Programacion;

use App\Models\Programacion\tbl_programacion_contrato;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReAsignacion
{
    /**
     * Función principal que orquesta todo el proceso
     */
    public function procesarYExportar($fecha)
    {
        $columnasTabla = Schema::getColumnListing('tbl_programacion_contratos');
        $elemento = array_splice($columnasTabla, 19, 1);
        array_splice($columnasTabla, 17, 0, $elemento);
        $columnasAExcluir = ['updated_at', 'created_at'];
        $columnasAIncluir = array_diff($columnasTabla, $columnasAExcluir);

        $busqueda = DB::table('tbl_programacion_contratos AS pc')
            ->join('tbl_programacion_base AS pb', 'pc.CONTRATO', '=', 'pb.CONTRATO')
            ->join('tbl_programacion_usuarios AS pu', 'pc.id_programacion', '=', 'pu.id')
            ->where('pc.FECHA_AGENDAMIENTO', '=', $fecha)
            ->where('pc.EJECUTADA', '=', 0)
            ->where('pu.finished', 1)
            ->where('pb.ESTADO_RECEPCION', '=', 0)
            ->select(
                'pc.id',
                'pc.CONTRATO',
                'pc.TIPO_TRABAJO',
                'pc.FECHA',
                'pc.CELULAR',
                'pc.NOMBRE_USUARIO',
                'pc.ORDEN_TRABAJO',
                'pc.DIRECCION',
                'pc.BARRIO',
                'pc.CIUDAD',
                'pc.ACTIVA',
                'pc.SUSPENDIDO',
                'pc.CATEGORIA',
                'pc.FECHA_AGENDAMIENTO',
                'pc.OBSERVACIONES',
                'pc.PORQUE_PROGRAMO',
                'pc.TECNICO',
                'pc.JORNADA',
                'pc.HORA_INICIO', // <-- AGREGADO PARA EL EXCEL
                'pc.HORA_FINAL'   // <-- AGREGADO PARA EL EXCEL
            );

        $plantilla = DB::table('tbl_programacion_contratos')
            ->where('FECHA_AGENDAMIENTO', '=', $fecha)
            ->where('EJECUTADA', '=', 0)
            ->where('plantilla', 1)
            ->select(
                'id',
                'CONTRATO',
                'TIPO_TRABAJO',
                'FECHA',
                'CELULAR',
                'NOMBRE_USUARIO',
                'ORDEN_TRABAJO',
                'DIRECCION',
                'BARRIO',
                'CIUDAD',
                'ACTIVA',
                'SUSPENDIDO',
                'CATEGORIA',
                'FECHA_AGENDAMIENTO',
                'OBSERVACIONES',
                'PORQUE_PROGRAMO',
                'TECNICO',
                'JORNADA',
                'HORA_INICIO', // <-- AGREGADO PARA EL EXCEL
                'HORA_FINAL'   // <-- AGREGADO PARA EL EXCEL
            );

        $plantilla = $plantilla->orderBy('TECNICO')->get();
        $busqueda = $busqueda->orderBy('TECNICO')->get();

        // 1. Concatenamos ambas colecciones (Plantilla queda de primera)
        $coleccionCombinada = $plantilla->concat($busqueda);

        if ($coleccionCombinada->isEmpty()) {
            return false; // Indicamos que no hay datos
        }

        // 1. Preparar estructuras y normalizar datos
        $estructuras = $this->prepararEstructuras($coleccionCombinada);

        // 2. Ejecutar la lógica de reasignación
        $this->ejecutarReasignacion($coleccionCombinada, $estructuras['carga'], $estructuras['tecnicos']);

        // 3. Generar y descargar el Excel
        return $this->generarExcel($coleccionCombinada, $fecha);
    }

    private function prepararEstructuras($programadas)
    {
        $cargaPorTecnico = [];
        $tecnicosPorUbicacion = [];

        foreach ($programadas as $prog) {
            $prog->JORNADA_NORMALIZADA = $this->normalizarJornada($prog->JORNADA);
            $prog->ES_RESIDENCIAL = $this->esUnidadResidencial($prog->DIRECCION);

            // 1. Limpiamos el nombre del barrio y lo guardamos en una nueva propiedad
            $prog->BARRIO_NORMALIZADO = $this->normalizarBarrio($prog->BARRIO);

            $tecnico = $prog->TECNICO;

            // 2. Usamos el barrio normalizado para armar la llave de la ubicación
            $ubicacion = $prog->CIUDAD . ' - ' . $prog->BARRIO_NORMALIZADO;

            $cargaPorTecnico[$tecnico] ??= ['AM' => 0, 'PM' => 0, 'ALL_DAY' => 0, 'TOTAL' => 0];

            $tecnicosPorUbicacion[$ubicacion] ??= [];
            if (!in_array($tecnico, $tecnicosPorUbicacion[$ubicacion])) {
                $tecnicosPorUbicacion[$ubicacion][] = $tecnico;
            }

            if ($prog->JORNADA_NORMALIZADA == 'AM') {
                $cargaPorTecnico[$tecnico]['AM']++;
            } elseif ($prog->JORNADA_NORMALIZADA == 'PM') {
                $cargaPorTecnico[$tecnico]['PM']++;
            } elseif ($prog->JORNADA_NORMALIZADA == 'ALL_DAY') {
                $cargaPorTecnico[$tecnico]['ALL_DAY']++;
            }

            $cargaPorTecnico[$tecnico]['TOTAL']++;
        }

        return ['carga' => $cargaPorTecnico, 'tecnicos' => $tecnicosPorUbicacion];
    }

    private function ejecutarReasignacion($programadas, &$cargaPorTecnico, $tecnicosPorUbicacion)
    {
        // =========================================================================
        // FASE 1: ATRACCIÓN DE TAREAS PARA "TECNICO MOVILIDAD"
        // =========================================================================

        $inspectoresMovilidadPorUbicacion = [];

        foreach ($programadas as $prog) {
            $motivo = strtoupper(trim($prog->PORQUE_PROGRAMO));
            if ($motivo === 'TECNICO MOVILIDAD') {
                // Usamos el barrio normalizado
                $ubicacion = $prog->CIUDAD . ' - ' . $prog->BARRIO_NORMALIZADO;
                $tec = $prog->TECNICO;
                $inspectoresMovilidadPorUbicacion[$ubicacion][$tec] = true;
            }
        }

        foreach ($programadas as $prog) {
            $motivo = strtoupper(trim($prog->PORQUE_PROGRAMO));
            // Usamos el barrio normalizado
            $ubicacion = $prog->CIUDAD . ' - ' . $prog->BARRIO_NORMALIZADO;
            $tecnicoActual = $prog->TECNICO;
            $jornada = $prog->JORNADA_NORMALIZADA;

            if ($motivo !== 'TECNICO MOVILIDAD' && isset($inspectoresMovilidadPorUbicacion[$ubicacion])) {
                foreach ($inspectoresMovilidadPorUbicacion[$ubicacion] as $tecnicoMovilidad => $val) {

                    if ($tecnicoActual !== $tecnicoMovilidad) {

                        $espacio = false;
                        if ($jornada == 'AM' && $cargaPorTecnico[$tecnicoMovilidad]['AM'] < 7) $espacio = true;
                        if ($jornada == 'PM' && $cargaPorTecnico[$tecnicoMovilidad]['PM'] < 7) $espacio = true;
                        if ($jornada == 'ALL_DAY' && $cargaPorTecnico[$tecnicoMovilidad]['TOTAL'] < 14) $espacio = true;

                        if ($espacio && $cargaPorTecnico[$tecnicoMovilidad]['TOTAL'] < 14) {
                            $prog->TECNICO = $tecnicoMovilidad;
                            $prog->OBSERVACIONES = "Atraído por Técnico Movilidad desde: " . $tecnicoActual . " | " . $prog->OBSERVACIONES;

                            $cargaPorTecnico[$tecnicoActual][$jornada]--;
                            $cargaPorTecnico[$tecnicoActual]['TOTAL']--;

                            $cargaPorTecnico[$tecnicoMovilidad][$jornada]++;
                            $cargaPorTecnico[$tecnicoMovilidad]['TOTAL']++;

                            break;
                        }
                    }
                }
            }
        }

        // =========================================================================
        // FASE 2: REASIGNACIÓN AUTOMÁTICA DEL CÓDIGO "100." (OFICINA)
        // =========================================================================

        foreach ($programadas as $prog) {
            $tec = $prog->TECNICO;
            $jornada = $prog->JORNADA_NORMALIZADA;
            // Usamos el barrio normalizado
            $ubicacion = $prog->CIUDAD . ' - ' . $prog->BARRIO_NORMALIZADO;

            if (strpos(trim($tec), '100.') === 0) {
                $this->buscarReemplazo($prog, $tec, $jornada, $ubicacion, $tecnicosPorUbicacion, $cargaPorTecnico);
            }
        }

        // =========================================================================
        // FASE 3: REASIGNACIÓN POR DESBORDAMIENTO DE LÍMITES (> 7 o > 14)
        // =========================================================================

        $programadasOrdenadas = $programadas->sortBy(function ($prog) {
            return strtoupper(trim($prog->PORQUE_PROGRAMO)) === 'TECNICO MOVILIDAD' ? 1 : 0;
        });

        foreach ($programadasOrdenadas as $prog) {
            $tec = $prog->TECNICO;
            $jornada = $prog->JORNADA_NORMALIZADA;
            // Usamos el barrio normalizado
            $ubicacion = $prog->CIUDAD . ' - ' . $prog->BARRIO_NORMALIZADO;

            $superaAM = ($jornada == 'AM' && $cargaPorTecnico[$tec]['AM'] > 7);
            $superaPM = ($jornada == 'PM' && $cargaPorTecnico[$tec]['PM'] > 7);
            $superaTotal = ($cargaPorTecnico[$tec]['TOTAL'] > 14);

            if ($superaAM || $superaPM || $superaTotal) {
                $this->buscarReemplazo($prog, $tec, $jornada, $ubicacion, $tecnicosPorUbicacion, $cargaPorTecnico);
            }
        }
    }

    private function buscarReemplazo($prog, $tecnicoOriginal, $jornada, $ubicacion, $tecnicosPorUbicacion, &$cargaPorTecnico)
    {
        // Buscamos técnicos que coincidan exactamente con la Ciudad y el Barrio
        $disponibles = $tecnicosPorUbicacion[$ubicacion] ?? [];

        foreach ($disponibles as $posibleTecnico) {
            if ($posibleTecnico == $tecnicoOriginal) continue;

            $espacio = false;
            if ($jornada == 'AM' && $cargaPorTecnico[$posibleTecnico]['AM'] < 7) $espacio = true;
            if ($jornada == 'PM' && $cargaPorTecnico[$posibleTecnico]['PM'] < 7) $espacio = true;
            if ($jornada == 'ALL_DAY' && $cargaPorTecnico[$posibleTecnico]['TOTAL'] < 14) $espacio = true;

            if ($espacio && $cargaPorTecnico[$posibleTecnico]['TOTAL'] < 14) {
                // Reasignar
                $prog->TECNICO = $posibleTecnico;
                $prog->OBSERVACIONES = "Reasignado de: " . $tecnicoOriginal . " | " . $prog->OBSERVACIONES;

                // Actualizar referencias
                $cargaPorTecnico[$tecnicoOriginal][$jornada]--;
                $cargaPorTecnico[$tecnicoOriginal]['TOTAL']--;
                $cargaPorTecnico[$posibleTecnico][$jornada]++;
                $cargaPorTecnico[$posibleTecnico]['TOTAL']++;
                break;
            }
        }
    }

    private function generarExcel($programadas, $fecha)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Encabezados en el Excel
        $encabezados = [
            'id', 'CONTRATO', 'TIPO_TRABAJ', 'FECHA', 'CELULAR', 'NOMBRE_US',
            'ORDEN_TRAE', 'DIRECCION', 'ES_RESIDENCIAL', 'BARRIO', 'CIUDAD', 'ACTIVA',
            'SUSPENDIDO', 'CATEGORIA', 'FECHA_AGEN', 'OBSERVACIO', 'PORQUE_PRO',
            'TECNICO', 'HORA_INICIO', 'HORA_FINAL', 'JORNADA', 'JORNADA_INTERPRETADA'
        ];

        $col = 'A';
        foreach ($encabezados as $encabezado) {
            $sheet->setCellValue($col++ . '1', $encabezado);
        }

        $fila = 2;
        foreach ($programadas as $prog) {
            // <-- CORREGIDOS: Se usan los nombres exactos del select de la BD
            $sheet->setCellValue('A' . $fila, $prog->id);
            $sheet->setCellValue('B' . $fila, $prog->CONTRATO);
            $sheet->setCellValue('C' . $fila, $prog->TIPO_TRABAJO);
            $sheet->setCellValue('D' . $fila, $prog->FECHA);
            $sheet->setCellValue('E' . $fila, $prog->CELULAR);
            $sheet->setCellValue('F' . $fila, $prog->NOMBRE_USUARIO);
            $sheet->setCellValue('G' . $fila, $prog->ORDEN_TRABAJO);
            $sheet->setCellValue('H' . $fila, $prog->DIRECCION);
            $sheet->setCellValue('I' . $fila, $prog->ES_RESIDENCIAL ? 'SI' : 'NO');
            $sheet->setCellValue('J' . $fila, $prog->BARRIO);
            $sheet->setCellValue('K' . $fila, $prog->CIUDAD);
            $sheet->setCellValue('L' . $fila, $prog->ACTIVA);
            $sheet->setCellValue('M' . $fila, $prog->SUSPENDIDO);
            $sheet->setCellValue('N' . $fila, $prog->CATEGORIA);
            $sheet->setCellValue('O' . $fila, $prog->FECHA_AGENDAMIENTO);
            $sheet->setCellValue('P' . $fila, $prog->OBSERVACIONES);
            $sheet->setCellValue('Q' . $fila, $prog->PORQUE_PROGRAMO);
            $sheet->setCellValue('R' . $fila, $prog->TECNICO);
            $sheet->setCellValue('S' . $fila, $prog->HORA_INICIO); // Ya está en el select
            $sheet->setCellValue('T' . $fila, $prog->HORA_FINAL);  // Ya está en el select
            $sheet->setCellValue('U' . $fila, $prog->JORNADA);
            $sheet->setCellValue('V' . $fila, $prog->JORNADA_NORMALIZADA);
            $fila++;
        }

        $writer = new Xlsx($spreadsheet);
        $nombreArchivo = 'Reasignacion_' . $fecha . '.xlsx';

        // Configuramos las cabeceras para que el controlador pueda retornarlas directamente
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function normalizarJornada($textoJornada)
    {
        if (empty($textoJornada)) return 'ALL_DAY';

        // Usamos mb_strtoupper con UTF-8 para que la 'ñ' se convierta correctamente a 'Ñ'
        $textoLimpio = mb_strtoupper(trim($textoJornada), 'UTF-8');

        if (in_array($textoLimpio, ['AM-PM', 'AM Y PM', 'AM - PM', 'AM/PM', 'TODO EL DIA', 'CUALQUIER', 'CUALQUIER JORNADA', 'CUALQUIERA', 'CUALQUIER MOMENTO', 'N/B'])) {
            return 'ALL_DAY';
        }

        // Agregamos 'MANANA' sin 'ñ' para prevenir errores de digitación en la base de datos
        if (in_array($textoLimpio, ['MAÑANA', 'MANANA', 'AM 07:00 - 12:00', 'AM'])) return 'AM';
        if (in_array($textoLimpio, ['TARDE'])) return 'PM';

        return 'ALL_DAY';
    }

    private function esUnidadResidencial($direccion)
    {
        if (empty($direccion)) return false;
        $direccion = strtoupper($direccion);
        $palabrasClave = ['CONJUNTO', 'UNIDAD', 'TORRE', 'APTO', 'APARTAMENTO', 'BLOQUE', 'INTERIOR', 'EDIFICIO', 'CR '];

        foreach ($palabrasClave as $palabra) {
            if (strpos($direccion, $palabra) !== false) return true;
        }
        return false;
    }

    private function normalizarBarrio($nombreBarrio)
    {
        if (empty($nombreBarrio)) return '';

        // Convertimos a mayúsculas y quitamos espacios a los lados
        $barrioLimpio = strtoupper(trim($nombreBarrio));

        // Lista de prefijos que queremos limpiar (incluyendo URBANIZACION y el error de tipeo VERDEDA)
        $prefijos = ['BARRIO ', 'URBANIZACION ', 'URBANIZACIÓN ', 'VEREDA ', 'VERDEDA '];

        foreach ($prefijos as $prefijo) {
            // strpos === 0 verifica si el texto empieza exactamente con esa palabra
            if (strpos($barrioLimpio, $prefijo) === 0) {
                // Cortamos la palabra del principio y limpiamos espacios sobrantes
                $barrioLimpio = trim(substr($barrioLimpio, strlen($prefijo)));
                break; // Solo quitamos un prefijo, así que podemos salir del ciclo
            }
        }

        return $barrioLimpio;
    }
}
