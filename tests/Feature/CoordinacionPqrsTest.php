<?php

namespace Tests\Feature;

use App\Models\AsignadasQuejas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * El tablero de PQRS ya traía lo importante: una lista blanca de columnas que
 * además cambia según el permiso. Lo que faltaba era la forma de lo que entra.
 *
 * Cuando la columna es ASIGNADO o RESPONSABLE, el controlador hace
 * `explode('.', $request->valor)` para sacar el identificador del inspector.
 * Con un arreglo ahí, eso es un error de tipo y un 500, no un mensaje.
 */
class CoordinacionPqrsTest extends TestCase
{
    use DatabaseTransactions;

    private function coordinador(): User
    {
        $usuario = User::create([
            'name' => 'Prueba PQRS',
            'email' => 'pqrs.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'coordinacion_pqrs')->firstOrFail());

        return $usuario;
    }

    private function queja(): AsignadasQuejas
    {
        $queja = AsignadasQuejas::first();

        if (! $queja) {
            $this->markTestSkipped('no hay quejas asignadas en esta base');
        }

        return $queja;
    }

    public function test_un_arreglo_en_el_valor_no_revienta_el_servidor(): void
    {
        $queja = $this->queja();

        $this->actingAs($this->coordinador())
            ->postJson(route('pqrs.coordinacion.updateAsignado'), [
                'orden' => $queja->NUMERO_ORDEN,
                'contrato' => $queja->CONTRATO,
                'campo' => 'ASIGNADO',
                'valor' => ['algo', 'inesperado'],
            ])
            ->assertStatus(422);
    }

    public function test_el_codigo_de_autorizacion_llega_como_numero_y_se_guarda(): void
    {
        /* CÓDIGO AUTORIZACIÓN está declarada `type: 'numeric'` en el
           Handsontable de la pantalla, así que lo tecleado se convierte a
           `Number` y sale como número JSON, no como texto. Validar `valor` con
           `string` lo rechazaba: la celda se revertía con «El valor de la
           celda no es válido» y no se podía guardar ningún código.

           Las demás pruebas de este archivo mandan cadenas, que es justo lo que
           la pantalla no manda en esta columna, y por eso el fallo llegó a
           producción. */
        $queja = $this->queja();

        $this->actingAs($this->coordinador())
            ->postJson(route('pqrs.coordinacion.updateAsignado'), [
                'orden' => $queja->NUMERO_ORDEN,
                'contrato' => $queja->CONTRATO,
                'campo' => 'CODIGO_AUTORIZACION',
                'valor' => 123456,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'fecha_extra' => date('Y-m-d')]);

        $fresca = $queja->fresh();

        $this->assertSame('123456', $fresca->CODIGO_AUTORIZACION);

        /* Guardar el código sella la fecha de respuesta, que es lo que la
           pantalla repinta en la columna de al lado. */
        $this->assertSame(date('Y-m-d'), $fresca->FECHA_RESPUESTA);
    }

    public function test_sin_orden_no_se_intenta_nada(): void
    {
        $queja = $this->queja();

        $this->actingAs($this->coordinador())
            ->postJson(route('pqrs.coordinacion.updateAsignado'), [
                'contrato' => $queja->CONTRATO,
                'campo' => 'OBSERVACION_SUPERVISOR',
                'valor' => 'algo',
            ])
            ->assertStatus(422);
    }

    public function test_la_lista_blanca_del_controlador_sigue_en_pie(): void
    {
        /* Una columna fuera de la lista sigue respondiendo el 404 con mensaje
           que la pantalla ya sabe revertir; validar no debía cambiarlo. */
        $queja = $this->queja();

        $this->actingAs($this->coordinador())
            ->postJson(route('pqrs.coordinacion.updateAsignado'), [
                'orden' => $queja->NUMERO_ORDEN,
                'contrato' => $queja->CONTRATO,
                'campo' => 'NUMERO_ORDEN',
                'valor' => '1',
            ])
            ->assertStatus(404);
    }
}
