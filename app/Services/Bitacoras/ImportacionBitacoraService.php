<?php

namespace App\Services\Bitacoras;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblDvInsp;
use App\Models\Bitacoras\TblTempContrato;
use App\Models\Bitacoras\TblTempFallida;
use App\Models\TblQuejasContrato;
use DateTime;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Volcado del Excel de inspecciones al borrador de la bitácora.
 *
 * Cada fila del archivo acaba en uno de tres sitios según cómo se cerró la
 * inspección: las que cuentan van al borrador (`tbl_temp_contratos`), las que
 * no se pudieron hacer van a fallidas, y las quejas a su propia tabla. Lo que
 * decide es la columna del resultado de cierre.
 */
class ImportacionBitacoraService
{
    /** Cierres que cuentan como inspección hecha. */
    private const CIERRES_VALIDOS = [
        'CERTIFICADA',
        'CERTIFICADA CON NOVEDADES',
        'INSPECCIONADA CON DEFECTO CRITICO VALLE',
        'INSPECCIONADA CON DEFECTO NO CRITICO VALLE',
    ];

    /** Cierres en los que la visita no llegó a hacerse. */
    private const CIERRES_FALLIDOS = [
        'EJECUTADA',
        '.ANULADO VALLE',
        '.DIRECCION NO ENCONTRADA',
        '.PREDIO EN CONSTRUCCION',
        'APLAZADO POR EL USUARIO.',
        'CASA SOLA.',
        'CERTIFICADA POR EYC.',
        'CERTIFICADA POR OIA EXTERNO.',
        'MEDIDOR POR LITROS BORRADOS.',
        'MENOR DE EDAD.',
        'NO ESTA EL ENCARGADO.',
        'NOVEDAD BLOQUEANTE',
        'NOVEDAD BLOQUEANTE.',
        'PERDIDA',
        'PREDIO DESOCUPADO.',
        'PROGRAMADA.',
        'USUARIO NO AUTORIZA.',
    ];

    /** Tipos de trabajo que se admiten como fallidas. */
    private const TIPOS_DE_TRABAJO = ['RP 10444', 'RN 12162', 'RP 12161', 'SA 12163', 'SA 12164'];

    private const TIPO_QUEJA = 'QUEJAS VALLE ';

    /** Columnas del archivo que se leen; el resto se ignora. */
    private const COLUMNAS = ['A', 'B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'M', 'N', 'O', 'Q', 'R', 'S'];

    /** Cantidades de recintos que no cuentan como «4 o más». */
    private const RECINTOS_INSUFICIENTES = ['1', '2', '3'];

    /**
     * Lee el archivo y deja el borrador listo para diligenciar.
     *
     * @param  list<string>  $cedulas  cédulas de los inspectores del supervisor
     * @param  string|null  $cierreTodos  '0' cuando la carga es de todos los supervisores
     */
    public function importar(Spreadsheet $libro, array $cedulas, ?int $idSupervisor, ?string $cierreTodos = null, ?string $nombreBitacora = null): TblBitacoraArchivo
    {
        $bitacora = $this->bitacoraEnCurso($nombreBitacora);

        [$inspecciones, $fallidas, $quejas] = $this->clasificarFilas($libro, $cedulas, $cierreTodos);

        /* Las quejas y las fallidas no se recogen cuando la carga es de todos
           los supervisores: en esa vía sólo interesan las inspecciones. */
        if ($cierreTodos !== '0') {
            $this->guardarQuejas($quejas);
            $this->guardarFallidas($fallidas, $bitacora, $idSupervisor);
        }

        $this->guardarInspecciones($inspecciones, $bitacora, $idSupervisor, $cierreTodos);

        return $bitacora;
    }

    /**
     * Las filas que ya están en el borrador de esta bitácora.
     */
    public function borradorDe(TblBitacoraArchivo $bitacora)
    {
        return TblTempContrato::where('id_bitacora', $bitacora->id)->get();
    }

    private function bitacoraEnCurso(?string $nombreBitacora): TblBitacoraArchivo
    {
        $usuario = Auth::user();

        $bitacora = TblBitacoraArchivo::where('id_usuario', $usuario->id)
            ->where('finished', 0)
            ->first();

        if ($bitacora) {
            return $bitacora;
        }

        /* El nombre llega como argumento; antes se leía de la sesión, que es
           un acoplamiento invisible entre el controlador y este servicio. */
        $nombre = $nombreBitacora ?? str_replace(['.xls', '4.08', ' V10'], [' ', '', ''], (string) session('nom_archivo'));

        $bitacora = new TblBitacoraArchivo;
        $bitacora->id_usuario = $usuario->id;
        $bitacora->NOMBRE_ARCHIVO = $nombre;
        $bitacora->ruta_archivo = 'storage/app/uploads/'.$nombre.'.xlsx';
        $bitacora->save();

        return $bitacora;
    }

    /**
     * Reparte las filas del archivo en los tres destinos.
     *
     * Se recorre el archivo una sola vez y se busca la cédula en un índice, en
     * lugar de releerlo entero por cada inspector: con 46 inspectores y 1.400
     * filas eso eran 64.000 vueltas leyendo las mismas celdas.
     *
     * @return array{0: array<string, list<array>>, 1: array<string, list<array>>, 2: array<string, list<array>>}
     */
    private function clasificarFilas(Spreadsheet $libro, array $cedulas, ?string $cierreTodos): array
    {
        $delSupervisor = array_flip(array_map('trim', $cedulas));

        $inspecciones = [];
        $fallidas = [];
        $quejas = [];

        foreach ($libro->getSheetNames() as $nombreHoja) {
            $hoja = $libro->getSheetByName($nombreHoja);

            foreach ($hoja->getRowIterator(2) as $fila) {
                $n = $fila->getRowIndex();

                $cedula = trim((string) $hoja->getCell('B'.$n)->getValue());

                if (! isset($delSupervisor[$cedula])) {
                    continue;
                }

                $contrato = (string) $hoja->getCell('H'.$n)->getValue();
                $cierre = ltrim((string) $hoja->getCell('M'.$n)->getValue(), '.');
                $tipoTrabajo = (string) $hoja->getCell('G'.$n)->getValue();
                $esContrato = str_starts_with($contrato, ':');

                if ($esContrato && in_array($cierre, self::CIERRES_VALIDOS, true)) {
                    $inspecciones[$cedula][] = $this->leerFila($hoja, $n, conCategoriaEspecial: true);

                    continue;
                }

                if ($cierreTodos === '0') {
                    continue;
                }

                if ($esContrato
                    && in_array($tipoTrabajo, self::TIPOS_DE_TRABAJO, true)
                    && in_array($cierre, self::CIERRES_FALLIDOS, true)) {
                    $fallidas[$cedula][] = $this->leerFila($hoja, $n);

                    continue;
                }

                if ($tipoTrabajo === self::TIPO_QUEJA && in_array($cierre, self::CIERRES_FALLIDOS, true)) {
                    $quejas[$cedula][] = $this->leerFila($hoja, $n);
                }
            }
        }

        return [$inspecciones, $fallidas, $quejas];
    }

    /**
     * Normaliza una fila del archivo.
     *
     * Estaba escrita tres veces, una por destino, con diferencias que no eran
     * intencionadas: sólo las inspecciones aplicaban las columnas alternativas
     * de RN 12162 y el periodo de gracia. Eso se conserva con
     * `$conCategoriaEspecial`, para no cambiar de paso lo que se guarda.
     */
    private function leerFila($hoja, int $n, bool $conCategoriaEspecial = false): array
    {
        $tipoTrabajo = (string) $hoja->getCell('G'.$n)->getValue();
        $datos = [];

        foreach (self::COLUMNAS as $columna) {
            $valor = $hoja->getCell($columna.$n)->getValue();

            if ($conCategoriaEspecial && $tipoTrabajo === 'RN 12162') {
                /* En revisión nueva la categoría y los recintos viajan en otras
                   dos columnas del mismo archivo. */
                if ($columna === 'S') {
                    $valor = $hoja->getCell('T'.$n)->getValue();
                }
                if ($columna === 'K') {
                    $valor = $hoja->getCell('L'.$n)->getValue();
                }
            }

            $datos[$columna] = match ($columna) {
                'A' => trim((string) $valor),
                'M' => ltrim((string) $valor, '.'),
                'D' => $this->fecha($valor),
                'Q' => $this->vencimiento($valor),
                'R' => $conCategoriaEspecial ? ($valor === 'Si' ? 1 : 0) : $valor,
                default => $valor,
            };
        }

        return $datos;
    }

    /**
     * Las fechas llegan como número de serie de Excel.
     */
    private function fecha(mixed $valor): mixed
    {
        return is_numeric($valor)
            ? Date::excelToDateTimeObject($valor)->format('y-m-d')
            : $valor;
    }

    /**
     * La columna de vencimiento no se guarda: se traduce a la marca de «60
     * meses», que es lo único que la pantalla usa, y sólo cuando vence este
     * mismo mes.
     */
    private function vencimiento(mixed $valor): string
    {
        $fecha = DateTime::createFromFormat('d/m/Y', (string) $valor);

        return ($fecha && $fecha->format('Y') === date('Y') && $fecha->format('m') === date('m'))
            ? '60 meses'
            : '';
    }

    private function guardarQuejas(array $quejas): void
    {
        foreach ($quejas as $inspecciones) {
            foreach ($inspecciones as $fila) {
                $repetida = TblQuejasContrato::where('CONTRATO', $fila['H'])
                    ->where('ORDEN_TRABAJO', $fila['I'])
                    ->where('No_ACTA', $fila['E'])
                    ->where('TIPO_TRABAJO', $fila['G'])
                    ->exists();

                if ($repetida) {
                    continue;
                }

                TblQuejasContrato::create($this->comunes($fila) + [
                    'CONTRATO' => ltrim((string) $fila['H'], ':'),
                ]);
            }
        }
    }

    private function guardarFallidas(array $fallidas, TblBitacoraArchivo $bitacora, ?int $idSupervisor): void
    {
        foreach ($fallidas as $inspecciones) {
            foreach ($inspecciones as $fila) {
                $repetida = TblTempFallida::where('CONTRATO', $fila['H'])
                    ->where('ORDEN_TRABAJO', $fila['I'])
                    ->where('No_ACTA', $fila['E'])
                    ->where('TIPO_TRABAJO', $fila['G'])
                    ->exists();

                if ($repetida) {
                    continue;
                }

                TblTempFallida::create($this->comunes($fila) + [
                    'CONTRATO' => $fila['H'],
                    'id_bitacora' => $bitacora->id,
                    'id_usuario' => Auth::id(),
                    'id_super' => $idSupervisor ?? 1,
                ]);
            }
        }
    }

    private function guardarInspecciones(array $inspecciones, TblBitacoraArchivo $bitacora, ?int $idSupervisor, ?string $cierreTodos): void
    {
        foreach ($inspecciones as $filas) {
            foreach ($filas as $fila) {
                $repetida = TblTempContrato::where('CONTRATO', $fila['H'])
                    ->where('ORDEN_TRABAJO', $fila['I'])
                    ->where('No_ACTA', $fila['E'])
                    ->where('TIPO_TRABAJO', $fila['G'])
                    ->exists();

                if ($repetida && $cierreTodos !== '0') {
                    continue;
                }

                TblTempContrato::create($this->comunes($fila) + [
                    'CONTRATO' => $fila['H'],
                    'ORDEN_TRABAJO' => $fila['I'],
                    'HORA_INICIO' => $fila['N'],
                    'HORA_FINAL' => $fila['O'],
                    '4_RECINTOS' => $this->recintos($fila['S'] ?? null),
                    'VENCE' => $fila['Q'],
                    'PERIODO_GRACIA' => $fila['R'],
                    'id_bitacora' => $bitacora->id,
                    'id_usuario' => Auth::id(),
                    'id_super' => $idSupervisor ?? 1,
                    'G_DEVOLUCION' => $this->tieneDevolucionPendiente($fila['H']) ? 1 : 0,
                ]);
            }
        }
    }

    /**
     * Los campos que los tres destinos comparten, con el mismo nombre.
     */
    private function comunes(array $fila): array
    {
        return [
            'NOMBRE' => $fila['A'],
            'CC_OPERARIO' => $fila['B'],
            'MUNICIPIO' => $fila['C'],
            'FECHA' => $fila['D'],
            'No_ACTA' => $fila['E'],
            'TIPO_TRABAJO' => $fila['G'],
            'ORDEN_TRABAJO' => $fila['I'],
            'ORDEN_EXT' => $fila['J'],
            'CATEGORIA' => $fila['K'],
            'RESULTADO_CIERRE' => $fila['M'],
        ];
    }

    /**
     * Menos de cuatro recintos no cuenta, y así se guarda.
     */
    private function recintos(mixed $valor): string
    {
        if ($valor === null || $valor === '' || in_array((string) $valor, self::RECINTOS_INSUFICIENTES, true)) {
            return 'NO';
        }

        return (string) $valor;
    }

    /**
     * Marca las inspecciones cuyo contrato arrastra una devolución sin
     * gestionar, para que la pantalla las destaque.
     */
    private function tieneDevolucionPendiente(mixed $contrato): bool
    {
        return TblDvInsp::where('CONTRATO', $contrato)->where('GESTIONADO', 0)->exists();
    }
}
