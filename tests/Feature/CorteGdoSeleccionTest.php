<?php

namespace Tests\Feature;

use App\Models\CorteGdo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Inicio: elegir qué corte de GDO se mira.
 *
 * Por omisión manda el vigente, pero al cerrarse uno hay que poder consultar
 * lo legalizado en los anteriores. El corte no sólo cambia esa cifra: los dos
 * acumulados arrancan en su fecha de inicio, así que todo el bloque tiene que
 * moverse junto o la tarjeta diría un periodo y las cifras otro.
 */
class CorteGdoSeleccionTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        return User::create([
            'name' => 'Prueba inicio',
            'email' => 'inicio.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);
    }

    /** Un corte cerrado, lejos de los reales para no solaparse con ellos. */
    private function corteViejo(): CorteGdo
    {
        return CorteGdo::create([
            'fecha_inicio' => '2019-03-01',
            'fecha_fin' => '2019-03-31',
        ]);
    }

    public function test_sin_pedir_corte_manda_el_vigente(): void
    {
        $this->corteViejo();
        $vigente = CorteGdo::vigente();

        $respuesta = $this->actingAs($this->usuario())
            ->getJson(route('home.reporte', ['fecha' => now()->format('Y-m-d')]))
            ->assertOk();

        $this->assertSame($vigente?->id, $respuesta->json('corte.id'));
    }

    public function test_se_puede_mirar_un_corte_cerrado(): void
    {
        $viejo = $this->corteViejo();

        $respuesta = $this->actingAs($this->usuario())
            ->getJson(route('home.reporte', ['fecha' => now()->format('Y-m-d'), 'corte' => $viejo->id]))
            ->assertOk();

        $this->assertSame($viejo->id, $respuesta->json('corte.id'));
        $this->assertSame('01/03/2019', $respuesta->json('corte.inicio_mostrado'));
        $this->assertTrue($respuesta->json('corte.cerrado'));

        /* Lo que se mide arranca en el corte elegido: si el acumulado siguiera
           en el vigente, la tarjeta y las cifras hablarían de periodos
           distintos sin avisar. */
        $this->assertSame('2019-03-01', $respuesta->json('acumuladoDesde'));
        $this->assertIsInt($respuesta->json('metricas.legalizado_corte'));
    }

    public function test_el_listado_trae_los_cortes_con_el_vigente_marcado(): void
    {
        $viejo = $this->corteViejo();

        $cortes = $this->actingAs($this->usuario())
            ->getJson(route('home.reporte', ['fecha' => now()->format('Y-m-d')]))
            ->assertOk()
            ->json('cortes');

        $ids = array_column($cortes, 'id');
        $this->assertContains($viejo->id, $ids);
        $this->assertSame([$viejo->id], array_column(array_filter($cortes, fn ($c) => $c['id'] === $viejo->id), 'id'));

        // Del más reciente al más antiguo, y el viejo de 2019 no es el vigente.
        $inicios = array_column($cortes, 'inicio');
        $ordenados = $inicios;
        rsort($ordenados);
        $this->assertSame($ordenados, $inicios);
        $this->assertFalse(collect($cortes)->firstWhere('id', $viejo->id)['vigente']);
    }

    public function test_un_corte_inexistente_se_rechaza(): void
    {
        $this->actingAs($this->usuario())
            ->getJson(route('home.reporte', ['fecha' => now()->format('Y-m-d'), 'corte' => 999999]))
            ->assertStatus(422)
            ->assertJsonPath('errors.corte.0', 'El corte que intentas mirar ya no existe.');
    }

    public function test_la_pantalla_tambien_abre_directa_en_un_corte(): void
    {
        $viejo = $this->corteViejo();

        $this->actingAs($this->usuario())
            ->get(route('home', ['corte_gdo' => $viejo->id]))
            ->assertOk()
            ->assertViewHas('corteGdo', fn ($corte) => $corte['id'] === $viejo->id)
            ->assertViewHas('cortesGdo', fn ($cortes) => count($cortes) >= 2);
    }
}
