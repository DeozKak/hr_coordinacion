<?php

namespace App\Services\Bitacoras;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblBitacorasCausal;
use App\Models\TblInspCali;
use App\Models\User;
use App\Models\Zonificacion\TblLocalidadesMunicipio;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Carga del Excel de inspecciones: de un archivo subido a un borrador abierto.
 *
 * Se ocupa de lo que rodea al volcado —guardar el archivo, comprobar que sea
 * el que toca, resolver los inspectores del supervisor y limpiar después— y
 * delega el reparto de las filas en {@see ImportacionBitacoraService}.
 */
class CargaBitacoraService
{
    /** La única hoja que el formato admite. */
    private const HOJA = '4.08 Bitacora Valle V10';

    /** Marca de «todos los supervisores» que llega del formulario. */
    public const TODOS = '0';

    public function __construct(private readonly ImportacionBitacoraService $importacion) {}

    /**
     * Procesa el archivo y devuelve lo que la pantalla necesita para pintarse.
     *
     * @return array{response: mixed, nombres: list<string>, cedulas: list<string>, inspectores: mixed, municipios: mixed, causales: mixed, id_super: int|null}
     *
     * @throws RuntimeException con el motivo, cuando el archivo no sirve
     */
    public function procesar(UploadedFile $archivo, ?User $supervisor): array
    {
        $todos = $supervisor === null;
        $nombreArchivo = $this->nombreDelArchivo($archivo, $supervisor);

        $this->exigirQueNoEsteEnCurso($nombreArchivo);

        $ruta = storage_path('app/'.$archivo->storeAs('uploads', $nombreArchivo));

        try {
            $libro = $this->abrir($ruta);
            $inspectores = $this->inspectoresDe($supervisor);

            $cedulas = $inspectores->pluck('cedula')->map(fn ($c) => trim((string) $c))->all();

            $bitacora = $this->importacion->importar(
                $libro,
                $cedulas,
                $supervisor?->id,
                $todos ? self::TODOS : null,
                $this->nombreDeLaBitacora($nombreArchivo)
            );
        } finally {
            /* El archivo ya está volcado a la base; conservarlo sólo llenaría
               `storage/app/uploads`, que es lo que hacía crecer el directorio. */
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }

        $filas = $this->importacion->borradorDe($bitacora);

        if ($filas->isEmpty()) {
            throw new RuntimeException('Error al generar la bitacora');
        }

        return [
            'response' => $filas,
            'nombres' => $inspectores->map(fn ($i) => $i->apellidos.' '.$i->nombres)->all(),
            'cedulas' => $cedulas,
            'inspectores' => $inspectores,
            'municipios' => TblLocalidadesMunicipio::all(),
            'causales' => TblBitacorasCausal::all(),
            'id_super' => $supervisor?->id,
        ];
    }

    /**
     * Nombre con el que se guarda: el del archivo más a quién pertenece.
     *
     * Los navegadores añaden « (1)» al descargar dos veces el mismo archivo, y
     * ese sufijo llegaba hasta el nombre de la bitácora.
     */
    private function nombreDelArchivo(UploadedFile $archivo, ?User $supervisor): string
    {
        $original = preg_replace('/\s*\(\d+\)(?=\.[A-Za-z0-9]+$|$)/', '', $archivo->getClientOriginalName());
        $original = preg_replace('/\.xlsx?$/i', '', $original);

        return $original.($supervisor?->name ?? 'Todos').'.xls';
    }

    /**
     * El nombre que lleva la bitácora, sin la versión ni la extensión.
     */
    private function nombreDeLaBitacora(string $nombreArchivo): string
    {
        return str_replace(['.xls', '4.08', ' V10'], [' ', '', ''], $nombreArchivo);
    }

    /**
     * Una bitácora sólo se carga una vez.
     */
    private function exigirQueNoEsteEnCurso(string $nombreArchivo): void
    {
        $nombre = $this->nombreDeLaBitacora($nombreArchivo);

        if (TblBitacoraArchivo::where('NOMBRE_ARCHIVO', $nombre)->where('finished', 1)->exists()) {
            throw new RuntimeException('El archivo seleccionado ya ha sido procesado');
        }

        if (TblBitacoraArchivo::where('NOMBRE_ARCHIVO', $nombre)->where('finished', 0)->exists()) {
            throw new RuntimeException('El archivo seleccionado se encuentra en proceso por otro usuario');
        }
    }

    private function abrir(string $ruta)
    {
        try {
            $libro = IOFactory::load($ruta);
        } catch (\Exception $e) {
            throw new RuntimeException('El archivo seleccionado no es válido o no se ha seleccionado un supervisor');
        }

        if (! $libro->sheetNameExists(self::HOJA)) {
            throw new RuntimeException('El archivo seleccionado no es válido o no se ha seleccionado un supervisor');
        }

        return $libro;
    }

    /**
     * Los inspectores activos del supervisor, o todos si no se indicó ninguno.
     */
    private function inspectoresDe(?User $supervisor)
    {
        return TblInspCali::where('state', 1)
            ->when($supervisor, fn ($q) => $q->where('SUPERVISOR', $supervisor->id))
            ->orderBy('apellidos')
            ->get();
    }
}
