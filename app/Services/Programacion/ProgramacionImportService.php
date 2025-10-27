<?php

namespace App\Services\Programacion;

use App\Jobs\ProcessExcelFileMacros;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProgramacionImportService
{

    private ExecutionVerifier $ExecutionVerifier;

    public function __construct(ExecutionVerifier $ExecutionVerifier)
    {
        $this->ExecutionVerifier = $ExecutionVerifier;
    }

    /**
     * @param UploadedFile $pathFile
     * @param string $type
     * @param string|null $nom_file
     * @return string[]
     */
    public function processFile($pathFile, string $type, string $nom_file = null): array
    {

        switch ($type) {
            case 'base':
                return $this::processAndDispatchBaseFile($pathFile, $nom_file);
                break;
            case 'programacion_tec':
                return $this::processMasivosFile($pathFile);
                break;
            case 'gdo':

                break;
            default:
                return ['errors' => 'Tipo de cargue no Autorizado'];
                break;
        }
        return ['errors' => 'Tipo de cargue no Autorizado'];
    }

    private function processAndDispatchBaseFile($file, $nom_file): array
    {
        $mapHeaders = [
            "Orden", "Contrato", "Producto", "Numero solicitud", "Tipo solicitud"
        ];

        try {
            // 2. Realizar la validación RÁPIDA de los encabezados
            $reader = ReaderEntityFactory::createXLSXReader();
            // Usamos storage_path() para obtener la ruta absoluta del archivo guardado
            $reader->open(storage_path('app/' . $file));

            $isValid = false;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    // Validamos la primera fila y salimos del bucle inmediatamente.
                    $isValid = $this->validacionConArray($row->toArray(), $mapHeaders);
                    break; // <-- Salimos después de leer la primera fila
                }
                break; // <-- Salimos después de leer la primera hoja
            }
            $reader->close();


            // 3. Si la validación falla, borramos el archivo y devolvemos un error.
            if (!$isValid) {
                Storage::delete($file); // Limpiamos el archivo subido
                return ['errors' => 'El archivo no cumple los criterios requeridos.'];
            }
            $originalName = $nom_file;// Obtener el nombre original

            // 4. Si todo está bien, despachamos el Job pasándole la ruta del archivo.
            ProcessExcelFileMacros::dispatch($file, Auth::user(), $originalName);

            // 5. Devolvemos una respuesta inmediata al usuario.
            // El código de estado 202 "Accepted" es ideal para esto.
            return ['message' => 'El archivo ha sido aceptado y se está procesando en segundo plano.'];

        } catch (\Exception  $e) {
            Log::error("Error en la subida inicial del archivo: " . $e->getMessage());
            return ['errors' => 'No se pudo procesar la solicitud de cargue.'];
        }


    }

    private function processMasivosFile($file):array
    {
        $mapHeaders = ['NN','Inspector','Categoria','Tipo de trabajo','Fecha de ejecucion','Codigo de Instalacion','Sector Operativo'
        ,'Direccion','Municipio'];

        $spreadsheet = IOFactory::load(storage_path('app/' . $file));
        $worksheet = $spreadsheet->getActiveSheet();
        $Array = $worksheet->toArray();
        $firstRow = $Array[0];

        $isValid = $this->validacionConArray($firstRow, $mapHeaders);

        if (!$isValid) {
            return ['errors' => 'El archivo no cumple los criterios requeridos.'];
        }


        //$indicador = $this->notificacion($datos);
        if ($datos === true) {
            session()->flash('success', 'Archivo subido exitosamente');
            return['message' => 'Archivo subido exitosamente'];
        } else {
            // en caso de error o no cumplir con requisitos $datos devuelve un string con el mensaje
            return ['errors' => $datos];
        }
    }

    private function validacionConArray(array $headerRow, array $expectedHeaders): bool
    {
        // Comparamos solo las primeras columnas necesarias
        $headersToValidate = array_slice($headerRow, 0, count($expectedHeaders));

        // Eliminamos espacios adicionales de los headers para evitar errores
        $headersToValidate = array_map('trim', $headersToValidate);
        $expectedHeaders = array_map('trim', $expectedHeaders);

        return $headersToValidate === $expectedHeaders;
    }


}
