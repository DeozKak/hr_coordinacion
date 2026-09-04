<?php

namespace Tests\Feature;

use App\Models\Nomina\TblNominaFechas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Los parámetros de precios multiplican la producción de cada inspector, así
 * que aceptar un texto donde va un importe sale caro. El controlador creía
 * cubrirlo con `is_numeric(intval($valor))`, que siempre es cierto.
 *
 * Estas pruebas fijan además el contrato de la respuesta, que es lo que se
 * podía romper al validar: la pantalla espera 200 con un `status` numérico y
 * traduce cada código a su mensaje. Un 422 le saldría como «Respuesta no
 * reconocida del servidor».
 */
class ReporteProduccionTest extends TestCase
{
    use DatabaseTransactions;

    private function usuarioCon(string $permiso): User
    {
        $usuario = User::create([
            'name' => 'Prueba reporte',
            'email' => 'reporte.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', $permiso)->firstOrFail());

        return $usuario;
    }

    private function periodoValido(array $cambios = []): array
    {
        return array_merge([
            'fechaPrecioInicio' => '2030-01',
            'fechaPrecioFin' => '2030-03',
            'metroRes' => 1000,
            'norteRes' => 1000,
            'caucaRes' => 1000,
            'metroCom' => 2000,
            'norteCom' => 2000,
            'caucaCom' => 2000,
            'inspeccionInd' => 500,
        ], $cambios);
    }

    public function test_un_precio_que_no_es_numero_se_rechaza_con_el_codigo_3(): void
    {
        $this->actingAs($this->usuarioCon('reporte_produccion'))
            ->postJson(route('fechasParametro.guardar'), $this->periodoValido(['metroRes' => 'abc']))
            ->assertOk()
            ->assertJson(['status' => 3]);
    }

    public function test_sin_fechas_responde_el_codigo_1(): void
    {
        $datos = $this->periodoValido();
        unset($datos['fechaPrecioInicio']);

        $this->actingAs($this->usuarioCon('reporte_produccion'))
            ->postJson(route('fechasParametro.guardar'), $datos)
            ->assertOk()
            ->assertJson(['status' => 1]);
    }

    public function test_la_respuesta_nunca_es_un_422(): void
    {
        /* La pantalla no distingue un 422: lo enseña como «Respuesta no
           reconocida del servidor». */
        $this->actingAs($this->usuarioCon('reporte_produccion'))
            ->postJson(route('fechasParametro.guardar'), ['fechaPrecioInicio' => 'no-es-un-mes'])
            ->assertOk();
    }

    public function test_la_proyeccion_diaria_admite_la_cadena_nan(): void
    {
        /* El Handsontable manda «NaN» al vaciar la celda y el controlador la
           traduce a cero; una regla numeric seca rompería esa vía. */
        $fecha = '2030-06-15';

        $this->actingAs($this->usuarioCon('reporte_produccion'))
            ->post(route('produccion.guardar'), ['fechaFila' => $fecha, 'nuevaCant' => 'NaN'])
            ->assertOk();

        $this->assertSame(
            0,
            (int) TblNominaFechas::where('fecha', $fecha)->first()?->cantidad_proyectada,
            'NaN debe guardarse como cero'
        );
    }

    public function test_la_proyeccion_diaria_rechaza_una_fecha_con_otro_formato(): void
    {
        $this->actingAs($this->usuarioCon('reporte_produccion'))
            ->postJson(route('produccion.guardar'), ['fechaFila' => '15/06/2030', 'nuevaCant' => 3])
            ->assertStatus(422);
    }
}
