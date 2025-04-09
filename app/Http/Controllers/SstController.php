<?php

namespace App\Http\Controllers;

use App\Models\tbl_insp_cali;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Illuminate\Support\Facades\Validator;

class SstController extends Controller
{

    public function index(){

        return view('sst.index');

    }

    public function ExportarPreoperacional(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'required|date_format:Y-m-d|before_or_equal:fecha_fin',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
        ],[
            'fecha_inicio.required' => 'El campo fecha inicio es obligatorio.',
            'fecha_fin.required' => 'El campo fecha fin es obligatorio.',
            'fecha_inicio.date_format' => 'El formato de la fecha de inicio debe ser (Y-mm-dd).',
            'fecha_fin.date_format' => 'El formato de la fecha de fin debe ser (Y-mm-dd).',
            'fecha_inicio.before_or_equal' => 'La fecha de inicio no puede ser mayor que la fecha final.',
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser menor que la fecha de inicio.',

        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $fecha_inicio = $request->fecha_inicio;
        $fecha_fin = $request->fecha_fin;

        $inspectores = tbl_insp_cali::where('state', 1)->get();

        // Convertir las cadenas en objetos DateTime
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);

        // Agregar un día al final para incluir la fecha de cierre
        $fin->modify('+1 day');

        // Crear un intervalo de un día
        $intervalo = new DateInterval('P1D');

        // Generar el rango de fechas
        $rango_fechas = new DatePeriod($inicio, $intervalo, $fin);

        // Ignoramos el token ya que no es relevante para el CSV
        $rows = [];
        $tipo_trabajo = '37148';
        $prioridad = '1690';
        $detalle = 'Crear el camino mas seguro es responsabilidad de todos';
        foreach ($inspectores as $inspector) {

            foreach ($rango_fechas as $fecha) {

                // Combinar fecha y hora en un formato que PHP pueda entender
                $fecha_hora_combinada_inicio = $fecha->format('Y-m-d') . ' ' . '07:00:00 a.m.';
                $fecha_hora_combinada_final = $fecha->format('Y-m-d') . ' ' . '08:00:00 p.m.';

                // Crear un objeto DateTime a partir de la fecha y hora combinada
                $objeto_fecha_inicio = DateTime::createFromFormat('Y-m-d h:i:s A', $fecha_hora_combinada_inicio);
                $objeto_fecha_final = DateTime::createFromFormat('Y-m-d h:i:s A', $fecha_hora_combinada_final);


                // Formatear la fecha en el formato deseado "d/m/Y h:i:s a"
                $fecha_formateada_inicio = $objeto_fecha_inicio->format('d/m/Y h:i:s a');
                $fecha_formateada_final = $objeto_fecha_final->format('d/m/Y h:i:s a');

                // Asegurarse de que la configuración regional esté en español para "a. m." y "p. m."
                setlocale(LC_TIME, 'es_ES.UTF-8');

                $rows[] = [
                    'GDO23434',
                    'PREOPERACIONAL VALLE',
                    $fecha_formateada_inicio,
                    $fecha_formateada_final,
                    'SST-NAL',
                    $inspector->cedula,
                    $tipo_trabajo,
                    $prioridad,
                    $detalle,
                    '',     //Nro de tarea interno
                    ''      //Código del bien (opcional)
                ];
            }

        }

        // Crear una nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Agregar los encabezados (opcional, pero recomendado)
        $headers = [
            'Nro contrato',
            'Direccion',
            'fecha Visita',
            'fecha Fin programado',
            'Grupo',
            'Nro Operario',
            'Id Tipo de Tarea',
            'Id Prioridad',
            'Detalle',
            'Nro de tarea interno',
            'Codigo del bien (opcional)'
        ];
        $sheet->fromArray($headers, NULL, 'A1');

        // Agregar los datos a la hoja
        $sheet->fromArray($rows, NULL, 'A2');

        // Crear el writer CSV
        $writer = new Csv($spreadsheet);

        // Establecer la configuración regional para el separador decimal (opcional)
        $writer->setDelimiter(';'); // Usar punto y coma como separador
        $writer->setEnclosure('');  // No usar ningún enclosure

        $writer->save(storage_path('app/uploads/') . 'Preoperacional' . ".csv");
        // Generar la URL de descarga

        // Puedes retornar la URL o usarla como necesites
        return response()->json(['url' => '../storage/app/uploads/Preoperacional.csv']);
    }

}
