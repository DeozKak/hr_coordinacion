<?php

namespace App\Http\Controllers\PQRS;

use App\Http\Controllers\Controller;
use App\Jobs\CorreoTiemposQuejas;
use App\Models\tbl_queja;
use App\Models\tbl_quejas_contrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PQRSImportController extends Controller
{
    public function index()
    {

        return view('pqrs.index');

    }

    public function getQuejas()
    {
        // Hacemos join para traer los datos del inspector
        $quejas = \App\Models\tbl_queja::query()
            ->select([
                // lista solo los campos que mostrarás (excluyendo id, created_at, updated_at)
                'CONTRATO',
                'LOCALIDAD',
                'BARRIO',
                'DIRECCION',
                'DIAS',
                // Armamos el string para inspector con alias
                \DB::raw("CONCAT(tbl_insp_cali.id, '. ', tbl_insp_cali.apellidos, ' ', tbl_insp_cali.nombres) AS INSPECTOR"),
                'recepcion'
            ])
            // Ajusta el nombre del campo 'inspector' según exista en tu modelo (puede ser id_inspector o similar)
            ->join('tbl_insp_cali', 'tbl_quejas.INSPECTOR', '=', 'tbl_insp_cali.id')
            ->get();

        return response()->json([
            'data' => $quejas
        ]);

    }


    public function import(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'macroPQR' => 'required|mimes:xls,xlsx,xlsm'
        ],
            [
                'macroPQR.required' => 'El archivo es requerido.',
                'macroPQR.mimes' => 'El archivo debe ser un archivo de Excel.'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {

            //cargar Macro Excel
            $spreadsheet = IOFactory::load($request->file('macroPQR'));
            // Seleccionar la hoja por su nombre
            $sheet = $spreadsheet->getActiveSheet();

            $indicador = $this->validateSheet($sheet);

            if (!$indicador) {
                return response()->json(['error' => 'El archivo no cumple los criterios requeridos'], 422);
            }

            $resultado = $this->insertion($sheet);

            if ($resultado['procesados'] > 0) {
                CorreoTiemposQuejas::dispatch();
            }


            return response()->json([
                'success' => true,
                'procesados' => $resultado['procesados'],
                'errores' => $resultado['errores']
            ]);

        } catch (\Exception $e) {
            log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    private function validateSheet($sheet): bool
    {
        $cabeceras = [
            'A' => "Orden",
            'B' => "Contrato",
            'C' => "Numero solicitud",
            'D' => "Tipo solicitud",
            'E' => "Descripción solicitud",
            'F' => "Cedula",
            'G' => "Nombre",
            'H' => "Departamento",
            'I' => "Localidad",
            'J' => "Barrio",
            'K' => "Dirección",
            'L' => "GPS",
            'M' => "Categoría",
            'N' => "Unidad",
            'O' => "Tipo trabajo",
            'P' => "Fecha creación",
            'Q' => "Observación solicitud"
        ];
        foreach ($cabeceras as $col => $valorEsperado) {
            if ($sheet->getCell($col . '1')->getValue() !== $valorEsperado) {
                return false;
            }
        }
        return true;
    }

    private function insertion($sheet)
    {
        $errores = [];
        $procesados = 0;

        tbl_queja::truncate();

        foreach ($sheet->getRowIterator() as $row) {
            // Saltar la cabecera (solo si la cabecera está en la fila 1)
            if ($row->getRowIndex() == 1) {
                continue;
            }

            try {
                DB::beginTransaction();
                $queja = new tbl_queja();
                $queja->CONTRATO = $sheet->getCell('B' . $row->getRowIndex())->getValue();
                $queja->LOCALIDAD = $sheet->getCell('I' . $row->getRowIndex())->getValue();
                $queja->BARRIO = $sheet->getCell('J' . $row->getRowIndex())->getValue();
                $queja->DIRECCION = $sheet->getCell('K' . $row->getRowIndex())->getValue();
                $queja->INSPECTOR = $sheet->getCell('AH' . $row->getRowIndex())->getValue();
                if($sheet->getCell('AO' . $row->getRowIndex())->getValue() === 1){
                    $queja->recepcion = "MACRO";
                }else{
                    $Consulta_movilidad = tbl_quejas_contrato::where('CONTRATO', $queja->CONTRATO)
                        ->where('ORDEN_TRABAJO', $sheet->getCell('A' . $row->getRowIndex())->getValue())
                        ->where('RESULTADO_CIERRE','EJECUTADA')
                        ->exists();
                    if ($Consulta_movilidad === true){
                        $queja->recepcion = 'GDW';
                    }

                }


                // Manejo fecha excel
                $valorExcel = $sheet->getCell('AI' . $row->getRowIndex())->getValue();
                $fechaAsignacion = null;
                $diferencia = null;

                if ($valorExcel !== null && $valorExcel !== '' && is_numeric($valorExcel)) {
                    try {
                        $fechaAsignacion = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valorExcel);
                        $fechaActual = new \DateTime();
                        $diferencia = $fechaActual->diff($fechaAsignacion)->days;
                    } catch (\Exception $fe) {
                        $errores[] = [
                            'fila' => $row->getRowIndex(),
                            'error' => "Fecha inválida: " . $fe->getMessage()
                        ];
                    }
                } else {
                    $errores[] = [
                        'fila' => $row->getRowIndex(),
                        'error' => "Fecha de asignación vacía."
                    ];
                }

                if ($diferencia !== null) {
                    $queja->DIAS = $diferencia;
                }

                $queja->save();
                DB::commit();
                $procesados++;

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error($e->getMessage());
                $errores[] = [
                    'fila' => $row->getRowIndex(),
                ];
            }
        }

        return [
            'errores' => $errores,
            'procesados' => $procesados
        ];
    }
}
