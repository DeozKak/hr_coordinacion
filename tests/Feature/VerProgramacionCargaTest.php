<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * La búsqueda de ver programación devuelve, junto a las filas, la carga de
 * cada técnico. Se comprueba contra la ruta real para asegurar lo que importa
 * en pantalla: que el listado cuente exactamente lo que muestra la tabla.
 */
class VerProgramacionCargaTest extends TestCase
{
    use DatabaseTransactions;

    /* Lejos de cualquier dato real, para que sólo cuenten las filas de la prueba. */
    private const FECHA = '2031-03-10';

    private function coordinador(): User
    {
        $usuario = User::create([
            'name' => 'Prueba programación',
            'email' => 'prog.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);
        $usuario->givePermissionTo(Permission::where('name', 'ver_programacion')->firstOrFail());

        return $usuario;
    }

    private function agendar(int $cuantas, string $tecnico, string $jornada, string $fecha = self::FECHA): void
    {
        $programacion = DB::table('tbl_programacion_usuarios')->value('id');

        if (! $programacion) {
            $this->markTestSkipped('no hay programaciones de usuario en esta base');
        }

        /* Filas de plantilla: la búsqueda las recoge sin cruzar con la base. */
        for ($i = 0; $i < $cuantas; $i++) {
            DB::table('tbl_programacion_contratos')->insert([
                'CONTRATO' => 'P'.uniqid(), 'NOMBRE_USUARIO' => 'x', 'ORDEN_TRABAJO' => 'N/A',
                'DIRECCION' => 'x', 'BARRIO' => 'x', 'CIUDAD' => 'CALI', 'ACTIVA' => 'SI',
                'SUSPENDIDO' => 'NO', 'CATEGORIA' => 'RESIDENCIAL', 'PORQUE_PROGRAMO' => 'prueba',
                'FECHA_AGENDAMIENTO' => $fecha, 'TECNICO' => $tecnico, 'JORNADA' => $jornada,
                'id_programacion' => $programacion, 'plantilla' => 1, 'EJECUTADA' => 0,
            ]);
        }
    }

    public function test_el_listado_cuenta_lo_mismo_que_la_tabla_y_alerta_la_sobrecarga(): void
    {
        $this->agendar(8, '901. TECNICO SOBRECARGADO', 'mañana');
        $this->agendar(2, '901. TECNICO SOBRECARGADO', 'todo el dia');
        $this->agendar(3, '902. TECNICO HOLGADO', 'tarde');

        $respuesta = $this->actingAs($this->coordinador())
            ->postJson(route('programacion.agendamiento'), ['fechaInicio' => self::FECHA])
            ->assertOk();

        $carga = $respuesta->json('carga');

        $this->assertSame(
            count($respuesta->json('data')),
            array_sum(array_column($carga, 'total')),
            'la suma del listado es el número de filas de la tabla'
        );

        $this->assertSame('901. TECNICO SOBRECARGADO', $carga[0]['tecnico'], 'el que más tiene va primero');
        $this->assertSame(10, $carga[0]['total']);
        $this->assertSame(['manana'], array_column($carga[0]['alertas'], 'tipo'));

        $this->assertSame('902. TECNICO HOLGADO', $carga[1]['tecnico']);
        $this->assertSame([], $carga[1]['alertas']);
    }

    public function test_en_un_rango_la_alerta_lleva_el_dia(): void
    {
        $this->agendar(4, '903. TECNICO RANGO', 'AM', '2031-03-10');
        $this->agendar(8, '903. TECNICO RANGO', 'PM', '2031-03-11');

        $carga = $this->actingAs($this->coordinador())
            ->postJson(route('programacion.agendamiento'), ['fechaInicio' => '2031-03-10', 'fechaFin' => '2031-03-11'])
            ->assertOk()
            ->json('carga');

        $this->assertSame(12, $carga[0]['total']);
        $this->assertSame([['fecha' => '2031-03-11', 'tipo' => 'tarde']],
            array_map(fn ($a) => ['fecha' => $a['fecha'], 'tipo' => $a['tipo']], $carga[0]['alertas']));
    }
}
