<?php

namespace App\Http\Controllers;

use App\Models\tbl_programacion_usuario;
use App\Models\tbl_programacion_base;
use App\Models\tbl_insp_cali;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Dotenv\Exception\ValidationException;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class ProgramacionController extends Controller
{

    public function index()
    {
        $datos = tbl_programacion_usuario::where('finished', 1)->with('usuario')->get();
        $validacion = tbl_programacion_usuario::where('finished', 0)->where('id_usuario', Auth::id())->exists();
        
        
        if($validacion){
            session()->flash('warning', 'Ya tienes una tabla de programación en curso ¿Deseas continuar?');
            
            return view('programacion.index', compact('datos'));
        }

        return view('programacion.index', compact('datos'));
    }

    public function create()
    {
        $validacion = tbl_programacion_usuario::where('finished', 0)->where('id_usuario', Auth::id())->exists();
        if($validacion){
            return redirect()->route('programacion.index')->with('error', 'Ya tienes una tabla de programación en curso');
        }

        
        $fechaActual = Carbon::now();
        $soloFecha = $fechaActual->format('Y-m-d');
        $programacion =  new tbl_programacion_usuario;
        $programacion->nombre = "Programación ".$soloFecha;
        $programacion->id_usuario = Auth::id();
        $programacion->save();



        $tecnicos = tbl_insp_cali::select('apellidos', 'nombres')
            ->where('state', 1)
            ->orderBy('apellidos') // Ordenar por apellidos ascendente
            ->get();

        $user = Auth::user();

        return view('programacion.create', compact('tecnicos', 'user'));
    }

    public function base(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:xls,xlsx',
            ], [
                'archivo.required' => 'El campo archivo es obligatorio.',
                'archivo.file' => 'El valor debe ser un archivo.',
                'archivo.mimes' => 'El archivo debe ser de tipo XLS o XLSX.',
            ]);

            $archivo = $request->file('archivo');
            $spreadsheet = IOFactory::load($archivo);
            $worksheet = $spreadsheet->getActiveSheet();
            $indicador = $this->validacion($worksheet);

            if (!$indicador) {
                return response()->json(['errors' => 'El archivo no cumple los criterios requeridos'], 422);
            }

            $valor = $this->insercion($worksheet);


            if ($valor === true) {
                return response()->json(['message' => 'Archivo subido exitosamente']);
            } else {
                return response()->json(['errors' => 'Error al subir el archivo'], 422);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e], 422);
        }
    }

    public function validacion($worksheet)
    {
        $indicador = true;
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $columna) {
            switch ($columna) {
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
                case 'F':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "BARRIO") ? true : false;
                    break;
                case 'G':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "DIRECCION") ? true : false;
                    break;
                case 'H':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "NOM_CATE") ? true : false;
                    break;
                case 'I':
                    $indicador = ($worksheet->getCell($columna . '1')->getValue() === "ID_TIPO_TRABAJO") ? true : false;
                    break;
            }
        }
        return $indicador;
    }

    public function insercion($worksheet)
    {
        $registros = []; // Array para almacenar los registros en lotes
        $tamañoLote = 2000; // Puedes ajustar el tamaño del lote según tus necesidades

        DB::beginTransaction(); // Iniciar una transacción

        try {
            foreach ($worksheet->getRowIterator() as $row) {
                if ($row->getRowIndex() === 1) {
                    continue; // Saltar la primera fila (encabezados)
                }
                $rowData = [];
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $columna) {
                    $valorCelda = $worksheet->getCell($columna . $row->getRowIndex())->getValue();

                    switch ($columna) {
                        case 'A':
                            $rowData["NUMERO_ORDEN"] = $valorCelda;
                            break;
                        case 'B':
                            $rowData["CONTRATO"] = $valorCelda;
                            break;
                        case 'C':
                            $rowData["DESC_ESTADO_PROD"] = $valorCelda;
                            break;
                        case 'D':
                            $rowData["NOMBRE"] = $valorCelda;
                            break;
                        case 'E':
                            $rowData["DESC_LOCALIDAD"] = $valorCelda;
                            break;
                        case 'F':
                            $rowData["BARRIO"] = $valorCelda;
                            break;
                        case 'G':
                            $rowData["DIRECCION"] = $valorCelda;
                            break;
                        case 'H':
                            $rowData["NOM_CATE"] = $valorCelda;
                            break;
                        case 'I':
                            $rowData["ID_TIPO_TRABAJO"] = $valorCelda;
                            break;
                    }
                }
                $registros[] = $rowData;

                if (count($registros) >= $tamañoLote) {
                    $this->insertarLoteConVerificacionDuplicados($registros);
                    $registros = [];
                }
            }
            // Insertar registros restantes (si los hay)
            if (!empty($registros)) {
                $this->insertarLoteConVerificacionDuplicados($registros);
            }

            DB::commit(); // Confirmar la transacción si todo tiene éxito
            return true;
        } catch (QueryException $e) {
            DB::rollback(); // Revertir la transacción si ocurre un error
            Log::error("Error al insertar datos: " . $e->getMessage()); // Registrar el error para depuración
            return false;
        }
    }

    // Función auxiliar para insertar un lote y verificar duplicados
    private function insertarLoteConVerificacionDuplicados($registros)
    {
        $numerosOrden = array_column($registros, 'NUMERO_ORDEN');
        $ordenesExistentes = tbl_programacion_base::whereIn('NUMERO_ORDEN', $numerosOrden)->pluck('NUMERO_ORDEN')->toArray();

        $registrosAInsertar = array_filter($registros, function ($registro) use ($ordenesExistentes) {
            return !in_array($registro['NUMERO_ORDEN'], $ordenesExistentes);
        });

        if (!empty($registrosAInsertar)) {
            tbl_programacion_base::insert($registrosAInsertar);
        }
    }

    public function busqueda($contrato)
    {
        // Validación numérica
        if (!is_numeric($contrato)) {
            return null; // O puedes devolver una respuesta adecuada, como un JSON vacío
        }

        if ($contrato == '') {
            return null;
        }

        $datos = tbl_programacion_base::where('CONTRATO', $contrato)->first();

        if ($datos) {
            return response()->json($datos);
        } else {
            return response()->json(['errors' => 'No se encontraron registros'], 422);
        }
    }
}
