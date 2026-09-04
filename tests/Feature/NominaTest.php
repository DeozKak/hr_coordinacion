<?php

namespace Tests\Feature;

use App\Models\Nomina\TblParametroSalAux;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Los parámetros de salario son la base de toda la liquidación. La
 * comprobación que había no sostenía nada: los importes pasaban por `intval()`
 * antes de preguntar `is_numeric()`, y los ocho porcentajes sólo se miraban
 * contra la cadena vacía, así que un texto entraba tal cual a la tabla.
 *
 * Las pruebas fijan además el contrato de la respuesta, que era lo fácil de
 * romper al validar: la pantalla espera 200 con un `status` numérico.
 */
class NominaTest extends TestCase
{
    use DatabaseTransactions;

    private function gestorDeNomina(): User
    {
        $usuario = User::create([
            'name' => 'Prueba nómina',
            'email' => 'nomina.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'gestion_nomina')->firstOrFail());

        return $usuario;
    }

    private function periodoValido(array $cambios = []): array
    {
        return array_merge([
            'fechaSalAuxInicio' => '2030-01',
            'fechaSalAuxFin' => '2030-12',
            'salMin' => 1750905,
            'auxTrans' => 249095,
            'salud' => 8.5,
            'pension' => 12,
            'arl' => 2.436,
            'caja' => 4,
            'prima' => 8.33,
            'cesantias' => 8.33,
            'intCesantias' => 1,
            'vacaciones' => 4.16,
        ], $cambios);
    }

    public function test_un_porcentaje_que_no_es_numero_se_rechaza(): void
    {
        $antes = TblParametroSalAux::count();

        $this->actingAs($this->gestorDeNomina())
            ->postJson(route('nomina.guardarSalarioAux'), $this->periodoValido(['salud' => 'abc']))
            ->assertOk()
            ->assertJson(['status' => 3]);

        $this->assertSame($antes, TblParametroSalAux::count(), 'no debe guardarse nada');
    }

    public function test_un_porcentaje_mayor_que_cien_se_rechaza(): void
    {
        $this->actingAs($this->gestorDeNomina())
            ->postJson(route('nomina.guardarSalarioAux'), $this->periodoValido(['pension' => 150]))
            ->assertOk()
            ->assertJson(['status' => 3]);
    }

    public function test_el_salario_minimo_no_puede_ser_negativo(): void
    {
        $this->actingAs($this->gestorDeNomina())
            ->postJson(route('nomina.guardarSalarioAux'), $this->periodoValido(['salMin' => -100]))
            ->assertOk()
            ->assertJson(['status' => 3]);
    }

    public function test_sin_fechas_responde_el_codigo_1(): void
    {
        $datos = $this->periodoValido();
        unset($datos['fechaSalAuxFin']);

        $this->actingAs($this->gestorDeNomina())
            ->postJson(route('nomina.guardarSalarioAux'), $datos)
            ->assertOk()
            ->assertJson(['status' => 1]);
    }

    public function test_la_respuesta_nunca_es_un_422(): void
    {
        /* La pantalla no sabe leer un 422: lo enseña como «Respuesta no
           reconocida del servidor». */
        $this->actingAs($this->gestorDeNomina())
            ->postJson(route('nomina.guardarSalarioAux'), [])
            ->assertOk();
    }

    public function test_la_multa_exige_un_mes_bien_formado(): void
    {
        /* El mes es parte de la clave con la que se busca la fila; con otro
           formato se crearía un registro paralelo que la liquidación no
           encuentra. */
        $this->actingAs($this->gestorDeNomina())
            ->postJson(route('nomina.guardarMultaRodamiento'), [
                'fecha' => '2030-10-05',
                'ccOperario' => '1113651976',
                'multa' => 50000,
            ])
            ->assertStatus(422);
    }

    public function test_la_multa_exige_un_inspector_que_exista(): void
    {
        $this->actingAs($this->gestorDeNomina())
            ->postJson(route('nomina.guardarMultaRodamiento'), [
                'fecha' => '2030-10',
                'ccOperario' => '00000000',
                'multa' => 50000,
            ])
            ->assertStatus(422);
    }
}
