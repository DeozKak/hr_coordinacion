<?php

namespace App\Services\Bitacoras;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Bitacoras\TblDvInsp;
use App\Models\TblInspCali;
use App\Models\User;
use App\Notifications\devolucion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Devoluciones: contratos que vuelven al inspector para que los rehaga.
 *
 * Se devuelve un contrato ya guardado —sale de `tbl_bitacora_contratos` y pasa
 * a `tbl_dv_insp`— y se gestiona cuando el inspector responde, momento en el
 * que puede volver a contar para producción.
 */
class DevolucionesService
{
    /**
     * Las mismas columnas que muestra la pantalla, en el mismo orden.
     */
    /** A partir de aquí medir cada celda sale más caro que fijar el ancho. */
    private const FILAS_PARA_ANCHO_AUTOMATICO = 500;

    private const COLUMNAS = [
        'Supervisor', 'Inspector', 'Fecha Inspección', 'Tipo Trabajo', 'Contrato',
        'Orden de trabajo', 'Orden Externa', 'Resultado', 'Causal', 'Fecha devolución',
        'Gestionado', 'Fecha gestión', 'Observación Gestión', 'Días sin Gestionar',
    ];

    /**
     * Excel de seguimiento: una hoja con lo pendiente y otra con el histórico.
     *
     * Antes se armaba con el HTML que enviaba el navegador —la propia tabla de
     * la pantalla, serializada y mandada de vuelta— y se guardaba en disco para
     * servirlo con un enlace firmado. Eso dejaba el contenido a merced de lo
     * que el cliente quisiera mandar y llenaba `storage/app/uploads` de
     * archivos que nadie volvía a abrir.
     *
     * Ahora sale de la base. La diferencia que se nota: exporta todas las
     * devoluciones, no sólo las que el filtro de la pantalla dejaba a la vista.
     */
    public function exportar(): Spreadsheet
    {
        $libro = new Spreadsheet;
        $libro->removeSheetByIndex(0);

        $this->hoja($libro, 'Devoluciones', $this->pendientes());
        $this->hoja($libro, 'Historicos', $this->gestionadas());

        $libro->setActiveSheetIndex(0);

        return $libro;
    }

    public function nombreDelExcel(): string
    {
        return 'Devoluciones '.date('Y-m-d').'.xlsx';
    }

    private function hoja(Spreadsheet $libro, string $titulo, Collection $devoluciones): void
    {
        $hoja = $libro->createSheet();
        $hoja->setTitle($titulo);

        $hoja->fromArray(self::COLUMNAS, null, 'A1');

        $n = 2;
        foreach ($devoluciones as $d) {
            $hoja->fromArray([
                $d->Supervisor->name ?? '—',
                trim(($d->Inspector->nombres ?? '').' '.($d->Inspector->apellidos ?? '')) ?: '—',
                (string) $d->FECHA_INSP,
                $d->TIPO_TRABAJO,
                $d->CONTRATO,
                $d->ORDEN_TRABAJO,
                $d->ORDEN_EXT,
                $d->RESULTADO_CIERRE,
                $d->CAUSAL,
                (string) $d->FECHA_DV,
                (int) $d->GESTIONADO === 1 ? 'Sí' : 'No',
                (string) $d->FECHA_GESTION,
                $d->OBSERVACION_GESTION,
                (int) $d->DIAS_SIN_GESTION,
            ], null, 'A'.$n);

            /* El aviso que la pantalla pinta en rojo: lleva demasiado tiempo
               sin que nadie la resuelva. */
            if ((int) $d->GESTIONADO !== 1 && (int) $d->DIAS_SIN_GESTION > 15) {
                $hoja->getStyle([1, $n, count(self::COLUMNAS), $n])
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE0E0');
            }

            $n++;
        }

        $this->rematar($hoja, $n - 1);
    }

    private function rematar(Worksheet $hoja, int $ultimaFila): void
    {
        $columnas = count(self::COLUMNAS);

        $encabezado = $hoja->getStyle([1, 1, $columnas, 1]);
        $encabezado->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $encabezado->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0096FF');
        $encabezado->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $hoja->freezePane('A2');
        $hoja->setAutoFilter('A1:'.$hoja->getCell([$columnas, 1])->getCoordinate());

        if ($ultimaFila > 1) {
            $hoja->getStyle([1, 1, $columnas, $ultimaFila])->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        /* El ancho automático obliga a PhpSpreadsheet a medir cada celda al
           guardar, y el histórico pasa de las 4.800 filas: sólo se pide en
           hojas pequeñas, y en las grandes se fija un ancho holgado. */
        $automatico = $ultimaFila <= self::FILAS_PARA_ANCHO_AUTOMATICO;

        for ($c = 1; $c <= $columnas; $c++) {
            $dimension = $hoja->getColumnDimensionByColumn($c);

            $automatico ? $dimension->setAutoSize(true) : $dimension->setWidth(22);
        }
    }

    /**
     * Las que siguen en circulación.
     *
     * Con el supervisor y el inspector cargados de una vez: el export recorre
     * miles de filas y pedirlos uno a uno eran ~9.600 consultas para el
     * histórico.
     */
    public function pendientes(): Collection
    {
        return TblDvInsp::with(['Supervisor', 'Inspector'])->where('ACTIVADO', 1)->get();
    }

    /** Las que ya salieron de circulación. */
    public function gestionadas(): Collection
    {
        return TblDvInsp::with(['Supervisor', 'Inspector'])->where('ACTIVADO', 0)->get();
    }

    /**
     * Devuelve contratos de una bitácora a sus inspectores.
     *
     * El contrato se mueve, no se copia: deja de contar en la bitácora y pasa
     * a estar pendiente de que lo rehagan. Va en una transacción porque a
     * mitad de camino el contrato ya no está en ningún sitio.
     *
     * @param  list<int|string>  $ids  contratos de `tbl_bitacora_contratos`
     * @return Collection los contratos devueltos
     */
    public function devolver(array $ids, int $idBitacora, ?string $causal): Collection
    {
        $archivo = TblBitacoraArchivo::find($idBitacora);

        if (! $archivo) {
            abort(404, 'La bitácora no existe.');
        }

        $contratos = TblBitacoraContrato::findMany($ids);

        DB::transaction(function () use ($contratos, $archivo, $idBitacora, $causal) {
            foreach ($contratos as $contrato) {
                $this->crearDevolucion($contrato, $archivo, $idBitacora, $causal);
                $contrato->delete();
            }
        });

        $this->avisarAlSupervisor($archivo, $contratos, $causal);

        return $contratos;
    }

    /**
     * Marca una devolución como resuelta.
     *
     * Si se pide, el contrato vuelve a producción: se recrea en
     * `tbl_bitacora_contratos` con los datos que la devolución guardó, salvo
     * que ya esté allí.
     */
    public function gestionar(TblDvInsp $devolucion, ?string $observacion, bool $devolverAProduccion): void
    {
        DB::transaction(function () use ($devolucion, $observacion, $devolverAProduccion) {
            $devolucion->GESTIONADO = 1;
            $devolucion->FECHA_GESTION = date('Y-m-d');
            $devolucion->OBSERVACION_GESTION = $observacion;
            $devolucion->save();

            if (! $devolverAProduccion || $this->yaEstaEnProduccion($devolucion)) {
                return;
            }

            $this->recrearContrato($devolucion);
        });
    }

    private function yaEstaEnProduccion(TblDvInsp $devolucion): bool
    {
        return TblBitacoraContrato::where('CONTRATO', $devolucion->CONTRATO)
            ->where('ORDEN_TRABAJO', $devolucion->ORDEN_TRABAJO)
            ->exists();
    }

    private function crearDevolucion(TblBitacoraContrato $contrato, TblBitacoraArchivo $archivo, int $idBitacora, ?string $causal): void
    {
        $devolucion = new TblDvInsp;

        $devolucion->SUPERVISOR = $archivo->id_usuario;
        $devolucion->INSPECTOR = TblInspCali::where('cedula', $contrato->CC_OPERARIO)->value('id');
        $devolucion->CC_OPERARIO = $contrato->CC_OPERARIO;
        $devolucion->MUNICIPIO = $contrato->MUNICIPIO;
        $devolucion->FECHA_INSP = $contrato->FECHA;
        $devolucion->No_ACTA = $contrato->No_ACTA;
        $devolucion->TIPO_TRABAJO = $contrato->TIPO_TRABAJO;
        $devolucion->CONTRATO = $contrato->CONTRATO;
        $devolucion->ORDEN_TRABAJO = $contrato->ORDEN_TRABAJO;
        $devolucion->ORDEN_EXT = $contrato->ORDEN_EXT;
        $devolucion->CATEGORIA = $contrato->CATEGORIA;
        $devolucion->RESULTADO_CIERRE = $contrato->RESULTADO_CIERRE;
        $devolucion->HORA_INICIO = $contrato->HORA_INICIO;
        $devolucion->HORA_FINAL = $contrato->HORA_FINAL;
        $devolucion->DURACION_INSP = $contrato->DURACION_INSP;
        $devolucion->setAttribute('4_RECINTOS', $contrato->getAttribute('4_RECINTOS'));
        $devolucion->vence = $contrato->vence;
        $devolucion->FECHA_DV = date('Y-m-d');
        $devolucion->GESTIONADO = 0;
        $devolucion->DIAS_SIN_GESTION = 0;
        $devolucion->id_bitacora = $idBitacora;
        $devolucion->ACTIVADO = 1;
        $devolucion->diseno_especial = 0;
        $devolucion->CAUSAL = $causal;
        $devolucion->save();
    }

    private function recrearContrato(TblDvInsp $devolucion): void
    {
        $contrato = new TblBitacoraContrato;

        $contrato->CC_OPERARIO = $devolucion->CC_OPERARIO;
        $contrato->MUNICIPIO = $devolucion->MUNICIPIO;
        $contrato->FECHA = $devolucion->FECHA_INSP;
        $contrato->No_ACTA = $devolucion->No_ACTA;
        $contrato->TIPO_TRABAJO = $devolucion->TIPO_TRABAJO;
        $contrato->CONTRATO = $devolucion->CONTRATO;
        $contrato->ORDEN_TRABAJO = $devolucion->ORDEN_TRABAJO;
        $contrato->ORDEN_EXT = $devolucion->ORDEN_EXT;
        $contrato->CATEGORIA = $devolucion->CATEGORIA;
        $contrato->RESULTADO_CIERRE = $devolucion->RESULTADO_CIERRE;
        $contrato->HORA_INICIO = $devolucion->HORA_INICIO;
        $contrato->HORA_FINAL = $devolucion->HORA_FINAL;
        $contrato->DURACION_INSP = $devolucion->DURACION_INSP;
        $contrato->setAttribute('4_RECINTOS', $devolucion->getAttribute('4_RECINTOS'));
        $contrato->id_bitacora = $devolucion->id_bitacora;
        $contrato->vence = $devolucion->vence;
        $contrato->state = 1;
        $contrato->save();
    }

    /**
     * El aviso no compromete la devolución: que el correo falle no es motivo
     * para dejar los contratos a medio camino.
     */
    private function avisarAlSupervisor(TblBitacoraArchivo $archivo, Collection $contratos, ?string $causal): void
    {
        $supervisor = User::find($archivo->id_usuario);

        if (! $supervisor || $contratos->isEmpty()) {
            return;
        }

        try {
            $supervisor->notify(new devolucion(
                Auth::user()?->name,
                $contratos->first()->CONTRATO,
                $supervisor->name,
                $archivo,
                $causal
            ));
        } catch (\Throwable $e) {
            Log::error($e);
        }
    }
}
