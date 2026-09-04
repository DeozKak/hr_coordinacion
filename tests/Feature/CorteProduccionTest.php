<?php

namespace Tests\Feature;

use App\Models\Produccion\TblProduccionCorte;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Crear un corte pasaba por Validator::make y editarlo no comprobaba nada, así
 * que el mismo formulario aceptaba o rechazaba según el botón que se pulsara.
 *
 * La pantalla distingue dos formas de error a propósito: un 422 con `error`
 * para la validación y un 200 con `status` para los tres choques de fechas.
 * Estas pruebas fijan que cada cosa siga por su carril.
 */
class CorteProduccionTest extends TestCase
{
    use DatabaseTransactions;

    private function residente(): User
    {
        $usuario = User::create([
            'name' => 'Prueba corte',
            'email' => 'corte.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'ver_residente')->firstOrFail());

        return $usuario;
    }

    private function corte(): TblProduccionCorte
    {
        $corte = TblProduccionCorte::first();
        $this->assertNotNull($corte, 'hace falta al menos un corte para esta prueba');

        return $corte;
    }

    private function datos(TblProduccionCorte $corte, array $cambios = []): array
    {
        return array_merge([
            'nombre' => $corte->nombre,
            'fecha_inicio' => $corte->fecha_inicio,
            'fecha_fin' => $corte->fecha_fin,
            'meta' => $corte->meta,
            'dobles' => $corte->dobles,
        ], $cambios);
    }

    public function test_editar_sin_nombre_se_rechaza(): void
    {
        $corte = $this->corte();

        $this->actingAs($this->residente())
            ->putJson(route('cortes_produccion.updateCorte', ['id' => $corte->id]),
                $this->datos($corte, ['nombre' => '']))
            ->assertStatus(422)
            ->assertJsonStructure(['error']);

        $this->assertSame($corte->nombre, $corte->fresh()->nombre);
    }

    public function test_la_meta_tiene_tope(): void
    {
        $corte = $this->corte();

        $this->actingAs($this->residente())
            ->putJson(route('cortes_produccion.updateCorte', ['id' => $corte->id]),
                $this->datos($corte, ['meta' => 9999]))
            ->assertStatus(422)
            ->assertJsonStructure(['error']);

        $this->assertSame((int) $corte->meta, (int) $corte->fresh()->meta);
    }

    public function test_la_meta_tiene_que_ser_un_entero(): void
    {
        $corte = $this->corte();

        $this->actingAs($this->residente())
            ->putJson(route('cortes_produccion.updateCorte', ['id' => $corte->id]),
                $this->datos($corte, ['meta' => 'muchas']))
            ->assertStatus(422);
    }

    public function test_las_fechas_invertidas_las_atrapa_la_validacion(): void
    {
        $corte = $this->corte();

        $this->actingAs($this->residente())
            ->putJson(route('cortes_produccion.updateCorte', ['id' => $corte->id]),
                $this->datos($corte, ['fecha_inicio' => '2030-05-10', 'fecha_fin' => '2030-05-01']))
            ->assertStatus(422)
            ->assertJsonStructure(['error']);
    }
}
