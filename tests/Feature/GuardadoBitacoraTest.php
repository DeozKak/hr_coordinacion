<?php

namespace Tests\Feature;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\Bitacoras\TblDvInsp;
use App\Models\Bitacoras\TblTempContrato;
use App\Models\User;
use App\Services\Bitacoras\AutoguardadoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * La bitácora definitiva se arma con el borrador de autoguardado, no con lo
 * que la pantalla mande. Es la única versión que se ha ido guardando sola, así
 * que es la única que sobrevive a que se cierre el navegador.
 *
 * Estas pruebas fijan además que el guardado sea atómico: antes el bloque que
 * cerraba la bitácora atrapaba su propia excepción y seguía adelante, de modo
 * que un fallo dejaba media bitácora escrita.
 */
class GuardadoBitacoraTest extends TestCase
{
    use DatabaseTransactions;

    private const CONTRATO = ':PRUEBA-999999';

    private function diligenciador(): User
    {
        $usuario = User::create([
            'name' => 'Prueba guardado',
            'email' => 'guardado.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'generar_bitacoras')->firstOrFail());

        return $usuario;
    }

    private function bitacoraEnCurso(User $usuario): TblBitacoraArchivo
    {
        $bitacora = new TblBitacoraArchivo;
        $bitacora->id_usuario = $usuario->id;
        $bitacora->nombre_archivo = 'Bitacora de prueba';
        $bitacora->ruta_archivo = 'storage/app/uploads/prueba.xlsx';
        $bitacora->finished = 0;
        $bitacora->save();

        return $bitacora;
    }

    private function filaBorrador(TblBitacoraArchivo $bitacora, User $usuario, array $cambios = []): TblTempContrato
    {
        $fila = new TblTempContrato;
        $fila->NOMBRE = 'INSPECTOR PRUEBA';
        $fila->CC_OPERARIO = '1113651976';
        $fila->MUNICIPIO = 'CALI';
        $fila->FECHA = '2030-01-15';
        $fila->No_ACTA = 'P'.random_int(100000, 999999);
        $fila->TIPO_TRABAJO = 'RP 10444';
        $fila->CONTRATO = self::CONTRATO.random_int(100, 999);
        $fila->ORDEN_TRABAJO = (string) random_int(100000, 999999);
        $fila->CATEGORIA = 'RESIDENCIAL';
        $fila->RESULTADO_CIERRE = 'CERTIFICADA';
        $fila->HORA_INICIO = '08:00';
        $fila->HORA_FINAL = '08:30';
        $fila->ESTADO = 'OK';
        $fila->id_bitacora = $bitacora->id;
        $fila->id_usuario = $usuario->id;
        $fila->id_super = $usuario->id;

        foreach ($cambios as $campo => $valor) {
            $fila->{$campo} = $valor;
        }

        $fila->save();

        return $fila;
    }

    public function test_la_bitacora_se_arma_con_lo_que_hay_en_el_borrador(): void
    {
        Queue::fake();

        $usuario = $this->diligenciador();
        $bitacora = $this->bitacoraEnCurso($usuario);
        $fila = $this->filaBorrador($bitacora, $usuario);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.guardar_tabla', ['super' => $usuario->id]))
            ->assertOk()
            ->assertJsonPath('resumen.contratos', 1);

        $guardado = TblBitacoraContrato::where('id_bitacora', $bitacora->id)->first();

        $this->assertNotNull($guardado, 'el contrato del borrador tiene que quedar guardado');
        $this->assertSame($fila->CONTRATO, $guardado->CONTRATO);
        $this->assertSame('00:30', $guardado->DURACION_INSP, 'la duración se calcula al guardar');

        $this->assertSame(1, (int) $bitacora->fresh()->finished, 'la bitácora queda cerrada');
        $this->assertSame(
            0,
            TblTempContrato::where('id_bitacora', $bitacora->id)->count(),
            'el borrador se vacía'
        );
    }

    public function test_una_fila_en_devolucion_va_a_devoluciones(): void
    {
        Queue::fake();

        $usuario = $this->diligenciador();
        $bitacora = $this->bitacoraEnCurso($usuario);
        $fila = $this->filaBorrador($bitacora, $usuario, [
            'ESTADO' => 'DV',
            'CAUSAL' => 'NO REALIZA INSPECCION',
        ]);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.guardar_tabla', ['super' => $usuario->id]))
            ->assertOk()
            ->assertJsonPath('resumen.devoluciones', 1)
            ->assertJsonPath('resumen.contratos', 0);

        $dv = TblDvInsp::where('id_bitacora', $bitacora->id)->first();

        $this->assertNotNull($dv);
        $this->assertSame($fila->CONTRATO, $dv->CONTRATO);
        $this->assertSame(0, (int) $dv->GESTIONADO, 'nace sin gestionar');
        $this->assertSame($usuario->id, (int) $dv->SUPERVISOR);
    }

    public function test_una_devolucion_sin_causal_no_guarda_nada(): void
    {
        Queue::fake();

        $usuario = $this->diligenciador();
        $bitacora = $this->bitacoraEnCurso($usuario);

        /* Una fila buena y otra en devolución sin causal: la buena tampoco debe
           quedar, porque el aviso salta antes de escribir. */
        $this->filaBorrador($bitacora, $usuario);
        $this->filaBorrador($bitacora, $usuario, [
            'ESTADO' => 'DV',
            'CAUSAL' => AutoguardadoService::SIN_CAUSAL,
        ]);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.guardar_tabla', ['super' => $usuario->id]))
            ->assertStatus(422)
            ->assertJsonStructure(['error']);

        $this->assertSame(
            0,
            TblBitacoraContrato::where('id_bitacora', $bitacora->id)->count(),
            'no debe quedar ningún contrato escrito'
        );
        $this->assertSame(0, (int) $bitacora->fresh()->finished, 'la bitácora sigue en curso');
        $this->assertSame(
            2,
            TblTempContrato::where('id_bitacora', $bitacora->id)->count(),
            'el borrador se conserva intacto para poder corregirlo'
        );
    }

    public function test_sin_supervisor_la_bitacora_se_descarta(): void
    {
        $usuario = $this->diligenciador();
        $bitacora = $this->bitacoraEnCurso($usuario);
        $this->filaBorrador($bitacora, $usuario);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.guardar_tabla'))
            ->assertOk();

        $this->assertNull(TblBitacoraArchivo::find($bitacora->id), 'la bitácora se elimina');
        $this->assertSame(0, TblTempContrato::where('id_bitacora', $bitacora->id)->count());
    }

    public function test_sin_bitacora_en_curso_avisa(): void
    {
        $usuario = $this->diligenciador();

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.guardar_tabla', ['super' => $usuario->id]))
            ->assertStatus(404);
    }
}
