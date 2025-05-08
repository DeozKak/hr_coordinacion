<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DescargasController extends Controller
{
    /**
     * Descargar archivos de forma segura.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */

    public function descargarArchivo(Request $request){

        $validator = Validator::make($request->all(), [
            'file' => 'required|string',
        ],
        [
            'file.required' => 'El nombre del archivo es requerido.',
            'file.string' => 'El nombre del archivo debe ser una cadena de texto.'
        ]);

        if ($validator->fails()) {
            abort(404, "El archivo no existe o no está permitido.");
        }

        // Directorio base permitido (por ejemplo, storage/app/uploads)
        $directorioPermitido = storage_path('app/uploads');

        // Nombre del archivo solicitado
        $nombreArchivo = basename($request->query('file')); // Asegurarse que solo sea el nombre y no rutas

        // Crear la ruta completa
        $rutaArchivo = $directorioPermitido . DIRECTORY_SEPARATOR . $nombreArchivo;

        // Verificar que el archivo existe y que está dentro del directorio permitido
        if (!file_exists($rutaArchivo) || !str_starts_with(realpath($rutaArchivo), realpath($directorioPermitido))) {
            abort(404, "El archivo no existe o no está permitido.");
        }

        // Descargar el archivo
        return response()->download($rutaArchivo, $nombreArchivo);

    }
}
