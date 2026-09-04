<?php

namespace Tests\Feature;

use App\Models\Bitacoras\TblBitacoraContrato;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * `ActualizarDetallesDiario` escribe la columna que le diga el navegador
 * (`$contrato->{$datos['prop']} = …`). Sin lista blanca eso alcanzaba a
 * cualquier campo de la tabla, incluidos los que mueven dinero: `CC_OPERARIO`
 * reasigna el contrato a otro inspector y la producción alimenta la nómina.
 * Estas pruebas fijan que sólo se puedan tocar las columnas que la propia
 * pantalla deja editar.
 */
class DetalleDiarioTest extends TestCase
{
    use DatabaseTransactions;

    private function residente(): User
    {
        $usuario = User::create([
            'name' => 'Residente de prueba',
            'email' => 'residente.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'ver_residente')->firstOrFail());

        return $usuario;
    }

    private function contrato(): TblBitacoraContrato
    {
        $contrato = TblBitacoraContrato::first();
        $this->assertNotNull($contrato, 'hace falta al menos un contrato para esta prueba');

        return $contrato;
    }

    public function test_no_se_puede_reasignar_el_contrato_a_otro_inspector(): void
    {
        $contrato = $this->contrato();
        $original = $contrato->CC_OPERARIO;

        $this->actingAs($this->residente())
            ->postJson(route('produccion.ActualizarDetallesDiario', ['id' => $contrato->id]), [
                'payload' => ['prop' => 'CC_OPERARIO', 'newValue' => '99999999'],
            ])
            ->assertStatus(422);

        $this->assertSame(
            $original,
            $contrato->fresh()->CC_OPERARIO,
            'el operario del contrato no debe cambiar'
        );
    }

    public function test_no_se_puede_esconder_un_contrato_de_la_produccion(): void
    {
        $contrato = $this->contrato();
        $original = $contrato->state;

        $this->actingAs($this->residente())
            ->postJson(route('produccion.ActualizarDetallesDiario', ['id' => $contrato->id]), [
                'payload' => ['prop' => 'state', 'newValue' => 0],
            ])
            ->assertStatus(422);

        $this->assertSame($original, $contrato->fresh()->state);
    }

    public function test_tampoco_se_puede_mudar_a_otra_bitacora(): void
    {
        $contrato = $this->contrato();
        $original = $contrato->id_bitacora;

        $this->actingAs($this->residente())
            ->postJson(route('produccion.ActualizarDetallesDiario', ['id' => $contrato->id]), [
                'payload' => ['prop' => 'id_bitacora', 'newValue' => 1],
            ])
            ->assertStatus(422);

        $this->assertSame($original, $contrato->fresh()->id_bitacora);
    }

    public function test_las_columnas_de_la_pantalla_si_se_editan(): void
    {
        $contrato = $this->contrato();

        $this->actingAs($this->residente())
            ->postJson(route('produccion.ActualizarDetallesDiario', ['id' => $contrato->id]), [
                'payload' => ['prop' => 'CATEGORIA', 'newValue' => 'COMERCIAL'],
            ])
            ->assertOk();

        $this->assertSame('COMERCIAL', $contrato->fresh()->CATEGORIA);
    }
}
