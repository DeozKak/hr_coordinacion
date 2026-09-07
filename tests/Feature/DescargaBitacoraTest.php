<?php

namespace Tests\Feature;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\User;
use App\Services\Bitacoras\ExcelBitacoraService;
use App\Services\Bitacoras\LecturaBitacoraService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * El Excel de una bitácora se arma al pedirlo, no al guardarla.
 *
 * Antes se escribía en disco dentro del guardado: el servidor acumulaba un
 * archivo por bitácora aunque nadie lo abriera, y un fallo de PhpSpreadsheet
 * —que ocurría con la transacción abierta— se llevaba por delante el guardado
 * entero.
 */
class DescargaBitacoraTest extends TestCase
{
    use DatabaseTransactions;

    private function lector(): User
    {
        $usuario = User::create([
            'name' => 'Prueba descarga',
            'email' => 'descarga.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'ver_bitacoras')->firstOrFail());

        return $usuario;
    }

    public function test_se_descarga_el_excel_de_una_bitacora(): void
    {
        $bitacora = TblBitacoraArchivo::where('finished', 1)->orderByDesc('id')->first();

        if (! $bitacora) {
            $this->markTestSkipped('no hay bitácoras guardadas en esta base');
        }

        $respuesta = $this->actingAs($this->lector())
            ->get(route('bitacoras.download', ['idBitacora' => $bitacora->id]))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

        $contenido = $respuesta->streamedContent();

        $this->assertNotEmpty($contenido, 'el archivo llega vacío');
        $this->assertSame('PK', substr($contenido, 0, 2), 'un .xlsx es un zip y empieza por PK');
    }

    public function test_una_bitacora_inexistente_no_revienta(): void
    {
        $this->actingAs($this->lector())
            ->get(route('bitacoras.download', ['idBitacora' => 99999999]))
            ->assertRedirect(route('bitacoras.reportes'));
    }

    public function test_hace_falta_permiso_para_descargar(): void
    {
        $sinPermiso = User::create([
            'name' => 'Sin permiso',
            'email' => 'sinpermiso.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $this->actingAs($sinPermiso)
            ->get(route('bitacoras.download', ['idBitacora' => 1]))
            ->assertRedirect(route('home'));
    }

    public function test_las_devoluciones_van_en_la_hoja_de_su_inspector_y_en_rojo(): void
    {
        $lectura = app(LecturaBitacoraService::class);

        $id = DB::table('tbl_dv_insp')
            ->where('GESTIONADO', 0)
            ->value('id_bitacora');

        $bitacora = $id ? TblBitacoraArchivo::find($id) : null;

        if (! $bitacora) {
            $this->markTestSkipped('no hay bitácoras con devoluciones pendientes en esta base');
        }

        $grupos = $lectura->filasPorInspector($bitacora->id);
        $inspector = $grupos->filter(fn ($f) => $f->where('es_devolucion', true)->isNotEmpty())->keys()->first();

        $libro = app(ExcelBitacoraService::class)->construir($bitacora);

        $this->assertNull(
            $libro->getSheetByName('DEVOLUCIONES'),
            'ya no hay hoja aparte: cada devolución va con su inspector'
        );

        $hoja = $libro->getSheetByName(mb_substr((string) $inspector, 0, 31));
        $this->assertNotNull($hoja, 'el inspector con devoluciones tiene su hoja');

        $rojas = 0;
        for ($f = 2; $f <= $hoja->getHighestRow(); $f++) {
            if ($hoja->getStyle([7, $f, 7, $f])->getFont()->getColor()->getRGB() === 'C00000') {
                $rojas++;
            }
        }

        $this->assertGreaterThan(0, $rojas, 'el contrato de una devolución se pinta en rojo');
    }

    public function test_cada_hoja_lleva_el_conteo_por_tipo_de_cierre(): void
    {
        $bitacora = TblBitacoraArchivo::where('finished', 1)->orderByDesc('id')->first();

        if (! $bitacora) {
            $this->markTestSkipped('no hay bitácoras guardadas en esta base');
        }

        $hoja = app(ExcelBitacoraService::class)
            ->construir($bitacora)
            ->getSheet(0);

        $etiquetas = [];
        for ($f = 1; $f <= $hoja->getHighestRow(); $f++) {
            $etiquetas[] = (string) $hoja->getCell([1, $f])->getValue();
        }

        $this->assertContains('RESUMEN POR TIPO DE CIERRE', $etiquetas);
        $this->assertContains('CERTIFICADA', $etiquetas);
        $this->assertContains('TOTAL CERRADAS', $etiquetas);
        $this->assertContains('DEVOLUCIONES', $etiquetas, 'se cuentan aparte, no dentro de los cierres');
    }
}
