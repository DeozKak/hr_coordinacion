<?php

namespace Tests\Feature;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Bitacoras\TblDvInsp;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pantalla de reportes: consultar una bitácora cerrada, sus indicadores,
 * buscarla por contrato y gestionar sus devoluciones.
 *
 * Son los endpoints que alimentan la vista del reporte y el Excel, así que si
 * alguno cambia de forma se rompen los dos a la vez.
 */
class ReporteBitacoraTest extends TestCase
{
    use DatabaseTransactions;

    private function lector(): User
    {
        $usuario = User::create([
            'name' => 'Prueba reporte bitácora',
            'email' => 'repbit.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'ver_bitacoras')->firstOrFail());

        return $usuario;
    }

    private function gestorDeDevoluciones(): User
    {
        $usuario = $this->lector();
        $usuario->givePermissionTo(Permission::where('name', 'mod_devoluciones')->firstOrFail());

        return $usuario;
    }

    private function bitacoraConContratos(): TblBitacoraArchivo
    {
        $id = TblBitacoraContrato::query()->value('id_bitacora');
        $bitacora = $id ? TblBitacoraArchivo::find($id) : null;

        if (! $bitacora) {
            $this->markTestSkipped('no hay bitácoras con contratos en esta base');
        }

        return $bitacora;
    }

    public function test_la_consulta_del_reporte_trae_las_columnas_que_pinta_la_pantalla(): void
    {
        $bitacora = $this->bitacoraConContratos();

        $contratos = $this->actingAs($this->lector())
            ->getJson(route('bitacoras.consulta_reporte', ['id_bitacora' => $bitacora->id]))
            ->assertOk()
            ->json('contratos');

        $this->assertNotEmpty($contratos);

        /* `nombre_completo` y `vence` se calculan en la consulta; si se caen,
           la tabla del reporte queda con columnas vacías sin avisar. */
        foreach (['nombre_completo', 'CONTRATO', 'RESULTADO_CIERRE', 'vence'] as $clave) {
            $this->assertArrayHasKey($clave, $contratos[0]);
        }
    }

    public function test_los_indicadores_cuadran_con_los_contratos_guardados(): void
    {
        $bitacora = $this->bitacoraConContratos();

        $indicadores = $this->actingAs($this->lector())
            ->getJson(route('bitacoras.Consulta_indicadores', ['id_bitacora' => $bitacora->id]))
            ->assertOk()
            ->json();

        $this->assertSame(
            TblBitacoraContrato::where('id_bitacora', $bitacora->id)->count(),
            $indicadores['totalContratosOK'],
            'el total tiene que ser el número de contratos de la bitácora'
        );

        $suma = $indicadores['certificadas']
            + $indicadores['certificadasConNovedades']
            + $indicadores['inspeccionadasConDefectoCritico']
            + $indicadores['inspeccionadasConDefectoNoCritico'];

        $this->assertLessThanOrEqual(
            $indicadores['totalContratosOK'],
            $suma,
            'los cierres contados no pueden superar el total'
        );
    }

    public function test_se_busca_una_bitacora_por_su_contrato(): void
    {
        $contrato = TblBitacoraContrato::query()->value('CONTRATO');

        if (! $contrato) {
            $this->markTestSkipped('no hay contratos en esta base');
        }

        $this->actingAs($this->lector())
            ->getJson(route('bitacoras.buscar_por_contrato', ['contrato' => $contrato]))
            ->assertOk()
            ->assertJsonCount(1, null);
    }

    public function test_gestionar_una_devolucion_la_marca_y_le_pone_fecha(): void
    {
        $devolucion = TblDvInsp::where('GESTIONADO', 0)->first();

        if (! $devolucion) {
            $this->markTestSkipped('no hay devoluciones pendientes en esta base');
        }

        $this->actingAs($this->gestorDeDevoluciones())
            ->post(route('bitacoras.actualizar_devolucion', ['id' => $devolucion->id]), [
                'observacion' => 'Gestionada en prueba',
                'agregar_produccion' => '0',
            ]);

        $fresca = $devolucion->fresh();

        $this->assertSame(1, (int) $fresca->GESTIONADO);
        $this->assertNotNull($fresca->FECHA_GESTION, 'queda constancia de cuándo se gestionó');
        $this->assertSame('Gestionada en prueba', $fresca->OBSERVACION_GESTION);
    }

    public function test_gestionar_una_devolucion_inexistente_no_revienta(): void
    {
        /* `find()` devuelve null y el método escribía sobre él sin comprobar. */
        $this->actingAs($this->gestorDeDevoluciones())
            ->post(route('bitacoras.actualizar_devolucion', ['id' => 99999999]), [
                'observacion' => 'x',
                'agregar_produccion' => '0',
            ])
            ->assertStatus(404);
    }

    public function test_el_export_de_devoluciones_sale_de_la_base(): void
    {
        /* Antes se armaba con el HTML que mandaba el navegador y se dejaba
           escrito en disco; ahora se construye aquí y se entrega en la
           respuesta, sin pasar por el cliente ni por `storage`. */
        $respuesta = $this->actingAs($this->lector())
            ->get(route('bitacora.exportar_devoluciones'))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

        $contenido = $respuesta->streamedContent();

        $this->assertNotEmpty($contenido);
        $this->assertSame('PK', substr($contenido, 0, 2), 'un .xlsx es un zip y empieza por PK');
    }

    public function test_el_export_no_deja_archivos_en_el_disco(): void
    {
        $antes = glob(storage_path('app/uploads/Devoluciones*.xlsx'));

        $this->actingAs($this->lector())
            ->get(route('bitacora.exportar_devoluciones'))
            ->assertOk()
            ->streamedContent();

        $this->assertSame(
            $antes,
            glob(storage_path('app/uploads/Devoluciones*.xlsx')),
            'el archivo se entrega en la respuesta, no se guarda'
        );
    }

    public function test_el_export_lleva_las_dos_hojas(): void
    {
        $libro = app(\App\Services\Bitacoras\DevolucionesService::class)->exportar();

        $this->assertNotNull($libro->getSheetByName('Devoluciones'), 'las pendientes');
        $this->assertNotNull($libro->getSheetByName('Historicos'), 'el histórico');
    }
}
