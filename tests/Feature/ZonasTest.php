<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Las relaciones de TblGruposDetalle se llamaban `tbl_grupo`, `tbl_subgrupo`,
 * `tbl_barrios` y `tbl_localidades_municipio`. Eloquent usa el nombre del
 * método como clave al serializar, así que ese nombre no era sólo interno:
 * viajaba hasta el JSON y la pantalla lo leía tal cual.
 *
 * Al acortarlos hubo que mover tres puntas a la vez —el modelo, los `with()`
 * del controlador y el script de la vista—, y un descuido no da error: la
 * columna sale vacía y nadie se entera. Esta prueba fija las claves que la
 * pantalla espera recibir.
 */
class ZonasTest extends TestCase
{
    use DatabaseTransactions;

    private function residente(): User
    {
        $usuario = User::create([
            'name' => 'Prueba zonas',
            'email' => 'zonas.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'ver_residente')->firstOrFail());

        return $usuario;
    }

    public function test_el_buscador_devuelve_las_relaciones_con_sus_nombres_nuevos(): void
    {
        $respuesta = $this->actingAs($this->residente())
            ->getJson(route('zonas.buscador'))
            ->assertOk();

        $filas = $respuesta->json('data') ?? $respuesta->json();

        if (! is_array($filas) || $filas === []) {
            $this->markTestSkipped('no hay relaciones de zonificación cargadas en esta base');
        }

        $fila = $filas[0];

        foreach (['grupo', 'subgrupo', 'barrio', 'municipio'] as $clave) {
            $this->assertArrayHasKey(
                $clave,
                $fila,
                "la pantalla lee fila.{$clave}; si falta, la columna sale vacía sin avisar"
            );
        }

        /* Y que los nombres viejos ya no aparezcan, para que no queden los dos. */
        foreach (['tbl_grupo', 'tbl_subgrupo', 'tbl_barrios', 'tbl_localidades_municipio'] as $viejo) {
            $this->assertArrayNotHasKey($viejo, $fila);
        }
    }
}
