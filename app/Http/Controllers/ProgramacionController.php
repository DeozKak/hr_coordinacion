<?php

namespace App\Http\Controllers;
use App\Models\tbl_programacion_usuario;
use App\Models\tbl_programacion_base;
use Illuminate\Validation\ValidationException;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class ProgramacionController extends Controller
{
    
    public function index()
    {
        $datos = tbl_programacion_usuario::with('usuario')->get();

        return view('programacion.index', compact('datos'));
    }

    public function create()
    {
        return view('programacion.create');
    }

    public function base(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:xls,xlsx', // Validación de archivo
            ]);
    
            $archivo = $request->file('archivo');
    
            $spreadsheet = IOFactory::load($archivo);

            
            $worksheet = $spreadsheet->getActiveSheet();
                
            $indicador = $this->validacion($worksheet);
           
            if(!$indicador){
                return response()->json(['errors' => 'El archivo no cumple con los criterios requeridos'], 422);
            }

            return response()->json(['message' => 'Archivo subido exitosamente']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e], 422);
        }
    }

    public function validacion($worksheet)
    {
        $indicador = true;
        foreach (['A', 'B', 'C', 'D', 'E', 'G', 'H', 'I'] as $columna) {
            switch($columna){
              case 'A':
                 $indicador = ($worksheet->getCell($columna . '1')->getValue() === "NUMERO_ORDEN") ? true : false;
                  break;
              case 'B':
                  $indicador = ($worksheet->getCell($columna . '1')->getValue() === "CONTRATO") ? true : false;
                  break;
              case 'C':
                  $indicador = ($worksheet->getCell($columna . '1')->getValue() === "DESC_ESTADO_PROD") ? true : false;
                  break;
              case 'D':
                  $indicador = ($worksheet->getCell($columna . '1')->getValue() === "NOMBRE") ? true : false;
                  break;
              case 'E':
                  $indicador = ($worksheet->getCell($columna . '1')->getValue() === "DESC_LOCALIDAD") ? true : false;
                  break;
              case 'G':
                  $indicador = ($worksheet->getCell($columna . '1')->getValue() === "DIRECCION") ? true : false;
                  break;
              case 'H':
                  $indicador = ($worksheet->getCell($columna . '1')->getValue() === "NOM_CATE")? true : false;
                  break;
              case 'I':
                  $indicador = ($worksheet->getCell($columna . '1')->getValue() === "ID_TIPO_TRABAJO") ? true : false;
                  break;
            }
          }
          return $indicador;
    }


    

}
