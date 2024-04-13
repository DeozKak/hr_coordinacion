<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\asignadas;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AsignadasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $asignadas = Asignadas::all();

        return view('asignadas.index', compact('asignadas'));
    }

    public function store(Request $request)
    {
        $response = AsignadasController::uploadFile($request);

        if (is_object($response)) {
            return redirect()->route('asignadas.load')->with('error', $response->errors()->first());
        }

        $spreadsheet = IOFactory::load($response);

        $array = AsignadasController::readExcel($spreadsheet);
        $skipFirstRow = false;
        $asignadas = [];

        foreach ($array as $item) {
            if ($skipFirstRow === false) {
                $skipFirstRow = true;
                continue; 
            }
            $num_entero = intval($item[23]);
            $fecha_vence = Date::excelToDateTimeObject($num_entero);
            $vence = $fecha_vence->format('Y-m-d');
            
            $asignada = [
                'nombre_lugar' => $item[6],
                'direccion' => $item[10],
                'departamento' => $item[7],
                'localidad' => $item[8],
                'contrato' => $item[1],
                'telefono' => $item[12],
                'email' => "",
                'emailCc' => "",
                'latitud' => null,
                'longitud' => null,
                'id_cliente' => 13776,
                'vence' => $vence,
                'categoria' => $item[14],
                'estado_producto' => $item[19],
                'estado_corte' => $item[20],
                'orden' => $item[0],
                'orden_externa' => null,
                'producto' => $item[2],
                'numero_solicitud' => $item[3],
                'tipo_trabajo' => $item[16],
                'sector_operativo' => $item[9],
                'unidad_operativa' => $item[15],
                'contratista' => "E&C INGENIERIA S.A.S",
                'fecha_asignacion' => Date::excelToDateTimeObject($item[17])->format('Y-m-d'),
                'fecha_externa' => null,
                'fecha_maximaEntrega' => $vence,
                'NIT_CC' => $item[5],
                'medidor' => $item[13],
            ];
           
            $asignadas[] = $asignada;
        }
        
        Asignadas::insert($asignadas);
        AsignadasController::eraseFile($response);
        return redirect()->route('asignadas.load')->with('success', 'Datos cargados correctamente.');
    }

    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|mimes:xls'
        ], [
            'archivo.mimes' => 'El archivo debe ser de tipo xls.'
        ]);

        if ($validator->fails()) {
            return $validator;
        }

        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            $nombreArchivo = $archivo->getClientOriginalName();
            $ruta_archivo =  $archivo->move(public_path('uploads'), $nombreArchivo);
            return $ruta_archivo->getLinkTarget();
        }
    }

    public function eraseFile($ruta_archivo){
        unlink($ruta_archivo); 
    }

    public function readExcel($spreadsheet): array
    {
        $data = [];

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($row = 1; $row <= $highestRow; ++$row) {
            $rowData = [];
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $value = $sheet->getCell([$col, $row])->getValue();
                $rowData[] = $value;
            }
            $data[] = $rowData;
        }

        return $data;
    }
}
