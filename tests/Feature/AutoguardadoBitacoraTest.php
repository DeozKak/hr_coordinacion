<?php

namespace Tests\Feature;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblTempContrato;
use App\Models\User;
use App\Services\Bitacoras\AutoguardadoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * El borrador de la bitácora es lo que después se convierte en la bitácora
 * definitiva, así que quien pueda escribir en él a su antojo escribe en la
 * bitácora. El controlador hacía `$contrato->$campo = $valor` con el nombre de
 * la columna llegando del navegador, y no comprobaba de quién era la fila.
 */
class AutoguardadoBitacoraTest extends TestCase
{
    use DatabaseTransactions;

    private function diligenciador(): User
    {
        $usuario = User::create([
            'name' => 'Prueba bitácora',
            'email' => 'bitacora.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'generar_bitacoras')->firstOrFail());

        return $usuario;
    }

    private function borrador(User $duenio): TblTempContrato
    {
        $bitacora = new TblBitacoraArchivo;
        $bitacora->id_usuario = $duenio->id;
        $bitacora->nombre_archivo = 'prueba';
        $bitacora->ruta_archivo = 'storage/app/uploads/prueba.xlsx';
        $bitacora->finished = 0;
        $bitacora->save();

        $fila = new TblTempContrato;
        $fila->NOMBRE = 'INSPECTOR PRUEBA';
        $fila->CC_OPERARIO = '1113651976';
        $fila->CONTRATO = ':999999';
        $fila->ORDEN_TRABAJO = '111';
        $fila->No_ACTA = 'P1';
        $fila->TIPO_TRABAJO = 'RP 10444';
        $fila->RESULTADO_CIERRE = 'CERTIFICADA';
        $fila->ESTADO = 'OK';
        $fila->id_super = $duenio->id;
        $fila->id_bitacora = $bitacora->id;
        $fila->id_usuario = $duenio->id;
        $fila->save();

        return $fila;
    }

    public function test_solo_se_pueden_editar_los_tres_campos_del_formulario(): void
    {
        $usuario = $this->diligenciador();
        $fila = $this->borrador($usuario);
        $contratoOriginal = $fila->CONTRATO;

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.actualizar', ['id' => $fila->id]), [
                'campo' => 'CONTRATO',
                'valor' => ':000000',
            ])
            ->assertStatus(422);

        $this->assertSame($contratoOriginal, $fila->fresh()->CONTRATO);
    }

    public function test_no_se_puede_mover_una_fila_al_borrador_de_otra_persona(): void
    {
        $usuario = $this->diligenciador();
        $fila = $this->borrador($usuario);
        $original = $fila->id_bitacora;

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.actualizar', ['id' => $fila->id]), [
                'campo' => 'id_bitacora',
                'valor' => '1',
            ])
            ->assertStatus(422);

        $this->assertSame($original, $fila->fresh()->id_bitacora);
    }

    public function test_no_se_edita_el_borrador_ajeno(): void
    {
        $duenio = $this->diligenciador();
        $ajeno = $this->diligenciador();
        $fila = $this->borrador($duenio);

        $this->actingAs($ajeno)
            ->postJson(route('bitacoras.actualizar', ['id' => $fila->id]), [
                'campo' => 'ESTADO',
                'valor' => 'DV',
            ])
            ->assertStatus(422);

        $this->assertSame('OK', $fila->fresh()->ESTADO);
    }

    public function test_el_estado_solo_admite_ok_o_dv(): void
    {
        $usuario = $this->diligenciador();
        $fila = $this->borrador($usuario);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.actualizar', ['id' => $fila->id]), [
                'campo' => 'ESTADO',
                'valor' => 'CUALQUIERA',
            ])
            ->assertStatus(422);

        $this->assertSame('OK', $fila->fresh()->ESTADO);
    }

    public function test_un_cambio_valido_responde_con_el_valor_guardado(): void
    {
        /* La pantalla pinta lo que devuelve el servidor, no lo que envió. */
        $usuario = $this->diligenciador();
        $fila = $this->borrador($usuario);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.actualizar', ['id' => $fila->id]), [
                'campo' => 'ESTADO',
                'valor' => 'DV',
            ])
            ->assertOk()
            ->assertJson(['campo' => 'ESTADO', 'valor' => 'DV']);

        $this->assertSame('DV', $fila->fresh()->ESTADO);
    }

    public function test_volver_a_ok_descarta_la_causal(): void
    {
        $usuario = $this->diligenciador();
        $fila = $this->borrador($usuario);
        $fila->ESTADO = 'DV';
        $fila->CAUSAL = 'ALGUNA CAUSAL';
        $fila->save();

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.actualizar', ['id' => $fila->id]), [
                'campo' => 'ESTADO',
                'valor' => 'OK',
            ])
            ->assertOk();

        $this->assertSame(
            AutoguardadoService::SIN_CAUSAL,
            $fila->fresh()->CAUSAL,
            'una fila que deja de ser devolución no conserva su motivo'
        );
    }
}
