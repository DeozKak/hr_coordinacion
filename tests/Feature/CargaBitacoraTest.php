<?php

namespace Tests\Feature;

use App\Models\Bitacoras\TblBitacoraArchivo;
use App\Models\Bitacoras\TblTempContrato;
use App\Models\Bitacoras\TblTempFallida;
use App\Models\TblInspCali;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Carga del Excel de inspecciones, que es la puerta de entrada del módulo:
 * de aquí sale el borrador que después se convierte en bitácora.
 *
 * El fixture es un archivo real de producción (`tests/Fixtures/bitacora-valle.xls`,
 * 1.402 inspecciones). Importa que lo sea: el formato tiene la hoja con un
 * nombre fijo, las fechas como número de serie de Excel y el reparto por
 * inspector hecho a base de comparar cédulas, y ninguna de esas tres cosas se
 * reproduce bien en un archivo inventado.
 */
class CargaBitacoraTest extends TestCase
{
    use DatabaseTransactions;

    private const FIXTURE = __DIR__.'/../Fixtures/bitacora-valle.xls';

    private function diligenciador(): User
    {
        $usuario = User::create([
            'name' => 'Prueba carga',
            'email' => 'carga.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $usuario->givePermissionTo(Permission::where('name', 'generar_bitacoras')->firstOrFail());

        return $usuario;
    }

    /**
     * Un supervisor que de verdad tenga inspectores en el archivo.
     *
     * Se busca en lugar de fijarlo: `guardar()` reparte las filas comparando la
     * cédula de cada una con las de los inspectores del supervisor elegido, así
     * que con uno que no tenga ninguna el borrador saldría vacío y la prueba
     * pasaría sin comprobar nada.
     */
    private function supervisorConInspectoresEnElArchivo(): int
    {
        $id = TblInspCali::where('state', 1)
            ->whereNotNull('SUPERVISOR')
            ->selectRaw('SUPERVISOR, COUNT(*) AS n')
            ->groupBy('SUPERVISOR')
            ->orderByDesc('n')
            ->value('SUPERVISOR');

        if (! $id || ! User::find($id)) {
            $this->markTestSkipped('no hay supervisores con inspectores activos en esta base');
        }

        return (int) $id;
    }

    /**
     * Una copia con nombre único: el controlador rechaza un archivo cuyo
     * nombre ya esté en proceso o procesado, y borra del disco el que sube.
     */
    private function archivo(): UploadedFile
    {
        $copia = sys_get_temp_dir().'/bitacora-'.uniqid().'.xls';
        copy(self::FIXTURE, $copia);

        return new UploadedFile($copia, 'PRUEBA_'.uniqid().'.xls', null, null, true);
    }

    public function test_el_archivo_llena_el_borrador(): void
    {
        $usuario = $this->diligenciador();
        $supervisor = $this->supervisorConInspectoresEnElArchivo();

        $antes = TblBitacoraArchivo::count();

        $this->actingAs($usuario)
            ->post(route('bitacoras.generar'), [
                'supervisor' => $supervisor,
                'archivo' => $this->archivo(),
            ])
            ->assertOk();

        $this->assertSame($antes + 1, TblBitacoraArchivo::count(), 'se abre una bitácora');

        $bitacora = TblBitacoraArchivo::where('id_usuario', $usuario->id)
            ->where('finished', 0)
            ->latest('id')
            ->first();

        $this->assertNotNull($bitacora, 'la bitácora nace en curso, no cerrada');

        $filas = TblTempContrato::where('id_bitacora', $bitacora->id)->count();
        $this->assertGreaterThan(0, $filas, 'el borrador se llena con las inspecciones del archivo');
    }

    public function test_las_inspecciones_llegan_con_sus_datos(): void
    {
        $usuario = $this->diligenciador();

        $this->actingAs($usuario)
            ->post(route('bitacoras.generar'), [
                'supervisor' => $this->supervisorConInspectoresEnElArchivo(),
                'archivo' => $this->archivo(),
            ])
            ->assertOk();

        $bitacora = TblBitacoraArchivo::where('id_usuario', $usuario->id)->where('finished', 0)->latest('id')->first();
        $fila = TblTempContrato::where('id_bitacora', $bitacora->id)->first();

        $this->assertNotNull($fila);

        foreach (['NOMBRE', 'CC_OPERARIO', 'CONTRATO', 'TIPO_TRABAJO', 'RESULTADO_CIERRE'] as $campo) {
            $this->assertNotEmpty($fila->{$campo}, "{$campo} no puede llegar vacío del Excel");
        }

        /* La columna se rellena por omisión aunque el Excel no la traiga: de
           ahí depende que el guardado sepa si la fila se queda o se devuelve. */
        $this->assertSame('OK', $fila->ESTADO);
    }

    public function test_los_cierres_fallidos_van_a_su_propia_tabla(): void
    {
        /* El archivo mezcla inspecciones cerradas con resultados del tipo
           «CASA SOLA.» o «CIERRE ADMINISTRATIVO», que no cuentan como
           inspección y viven aparte. */
        $usuario = $this->diligenciador();

        $this->actingAs($usuario)
            ->post(route('bitacoras.generar'), [
                'supervisor' => $this->supervisorConInspectoresEnElArchivo(),
                'archivo' => $this->archivo(),
            ])
            ->assertOk();

        $bitacora = TblBitacoraArchivo::where('id_usuario', $usuario->id)->where('finished', 0)->latest('id')->first();

        $this->assertGreaterThan(
            0,
            TblTempFallida::where('id_bitacora', $bitacora->id)->count(),
            'este archivo trae cierres fallidos; si no aparece ninguno, el reparto se rompió'
        );
    }

    public function test_sin_supervisor_no_se_carga(): void
    {
        $this->actingAs($this->diligenciador())
            ->post(route('bitacoras.generar'), ['archivo' => $this->archivo()])
            ->assertRedirect(route('bitacora'));

        $this->assertSame(0, TblBitacoraArchivo::where('finished', 0)->whereDate('created_at', today())->count());
    }

    public function test_sin_archivo_no_se_carga(): void
    {
        $this->actingAs($this->diligenciador())
            ->post(route('bitacoras.generar'), ['supervisor' => $this->supervisorConInspectoresEnElArchivo()])
            ->assertRedirect(route('bitacora'));
    }

    public function test_un_excel_con_otra_hoja_se_rechaza(): void
    {
        /* El controlador exige que la hoja se llame «4.08 Bitacora Valle V10»;
           cualquier otro libro es un archivo equivocado. */
        $otro = sys_get_temp_dir().'/otro-'.uniqid().'.xlsx';
        $libro = new Spreadsheet;
        $libro->getActiveSheet()->setTitle('Hoja cualquiera')->setCellValue('A1', 'nada');
        (new Xlsx($libro))->save($otro);

        $this->actingAs($this->diligenciador())
            ->post(route('bitacoras.generar'), [
                'supervisor' => $this->supervisorConInspectoresEnElArchivo(),
                'archivo' => new UploadedFile($otro, 'equivocado.xlsx', null, null, true),
            ])
            ->assertRedirect(route('bitacora'))
            ->assertSessionHas('error');
    }
}
