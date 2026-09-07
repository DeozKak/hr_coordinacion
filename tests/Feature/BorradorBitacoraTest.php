<?php

namespace Tests\Feature;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblTempContrato;
use App\Models\TblInspCali;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Ciclo de vida del borrador: añadir una inspección en papel, restaurarlo y
 * descartarlo.
 *
 * El borrador es la bitácora en construcción, así que quien pueda tocarlo o
 * borrarlo toca la bitácora de otra persona.
 */
class BorradorBitacoraTest extends TestCase
{
    use DatabaseTransactions;

    private function diligenciador(): User
    {
        $usuario = User::create([
            'name' => 'Prueba borrador',
            'email' => 'borrador.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'generar_bitacoras')->firstOrFail());

        return $usuario;
    }

    private function bitacoraDe(User $usuario, int $finished = 0): TblBitacoraArchivo
    {
        $bitacora = new TblBitacoraArchivo;
        $bitacora->id_usuario = $usuario->id;
        $bitacora->nombre_archivo = 'Bitacora de prueba';
        $bitacora->ruta_archivo = 'storage/app/uploads/prueba.xlsx';
        $bitacora->finished = $finished;
        $bitacora->save();

        return $bitacora;
    }

    private function cedulaDeInspector(): string
    {
        /* Hay inspectores activos con la cédula en '0', que PHP toma por
           vacía; se pide una de verdad. */
        $cedula = TblInspCali::where('state', 1)
            ->whereNotNull('cedula')
            ->where('cedula', '<>', '')
            ->where('cedula', '<>', '0')
            ->value('cedula');

        if (! $cedula) {
            $this->markTestSkipped('no hay inspectores activos con cédula en esta base');
        }

        return (string) $cedula;
    }

    // ---------------------------------------------------------------
    // Añadir una inspección hecha en papel
    // ---------------------------------------------------------------

    private function inspeccionEnPapel(TblBitacoraArchivo $bitacora, User $usuario, array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'INSPECTOR PRUEBA',
            'cedula' => $this->cedulaDeInspector(),
            'municipio' => 'CALI',
            'fecha' => '2030-01-15',
            'acta' => 'P'.random_int(100000, 999999),
            'tipoTrabajo' => 'RP 10444',
            'contrato' => ':'.random_int(100000, 999999),
            'categoria' => 'RESIDENCIAL',
            'cantidadRecintos' => 'NO',
            'resultadoCierre' => 'CERTIFICADA',
            'rechazo' => null,
            'id_bitacora' => $bitacora->id,
            'id_super' => $usuario->id,
        ], $cambios);
    }

    public function test_una_inspeccion_en_papel_entra_al_borrador_con_estado_ok(): void
    {
        $usuario = $this->diligenciador();
        $bitacora = $this->bitacoraDe($usuario);

        $respuesta = $this->actingAs($usuario)
            ->postJson(route('bitacoras.agregar'), [
                'datos' => $this->inspeccionEnPapel($bitacora, $usuario),
            ])
            ->assertOk()
            ->assertJsonStructure(['id']);

        $fila = TblTempContrato::find($respuesta->json('id'));

        $this->assertNotNull($fila);
        $this->assertSame('OK', $fila->ESTADO, 'nace en OK, no sin estado');
        $this->assertSame($usuario->id, (int) $fila->id_usuario);
    }

    public function test_una_inspeccion_con_cedula_inventada_se_rechaza(): void
    {
        $usuario = $this->diligenciador();
        $bitacora = $this->bitacoraDe($usuario);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.agregar'), [
                'datos' => $this->inspeccionEnPapel($bitacora, $usuario, ['cedula' => '00000000']),
            ])
            ->assertStatus(422);
    }

    public function test_una_inspeccion_sin_contrato_se_rechaza(): void
    {
        $usuario = $this->diligenciador();
        $bitacora = $this->bitacoraDe($usuario);

        $datos = $this->inspeccionEnPapel($bitacora, $usuario);
        unset($datos['contrato']);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.agregar'), ['datos' => $datos])
            ->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Descartar el borrador
    // ---------------------------------------------------------------

    public function test_descartar_el_borrador_propio_lo_elimina(): void
    {
        $usuario = $this->diligenciador();
        $bitacora = $this->bitacoraDe($usuario);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.borrar', ['id' => $bitacora->id]))
            ->assertOk();

        $this->assertNull(TblBitacoraArchivo::find($bitacora->id));
    }

    public function test_nadie_descarta_el_borrador_de_otra_persona(): void
    {
        $duenio = $this->diligenciador();
        $ajeno = $this->diligenciador();
        $bitacora = $this->bitacoraDe($duenio);

        $this->actingAs($ajeno)
            ->postJson(route('bitacoras.borrar', ['id' => $bitacora->id]))
            ->assertStatus(403);

        $this->assertNotNull(
            TblBitacoraArchivo::find($bitacora->id),
            'el borrador de otra persona no se puede eliminar'
        );
    }

    public function test_una_bitacora_ya_cerrada_no_se_puede_borrar(): void
    {
        /* Una bitácora cerrada es un reporte: sus contratos viven en
           tbl_bitacora_contratos y borrar el archivo los dejaría huérfanos. */
        $usuario = $this->diligenciador();
        $bitacora = $this->bitacoraDe($usuario, finished: 1);

        $this->actingAs($usuario)
            ->postJson(route('bitacoras.borrar', ['id' => $bitacora->id]))
            ->assertStatus(422);

        $this->assertNotNull(TblBitacoraArchivo::find($bitacora->id));
    }

    // ---------------------------------------------------------------
    // Restaurar el borrador
    // ---------------------------------------------------------------

    public function test_nadie_restaura_el_borrador_de_otra_persona(): void
    {
        $duenio = $this->diligenciador();
        $ajeno = $this->diligenciador();
        $bitacora = $this->bitacoraDe($duenio);

        $this->actingAs($ajeno)
            ->get(route('bitacoras.restaurar', ['id' => $bitacora->id]))
            ->assertStatus(403);
    }
}
