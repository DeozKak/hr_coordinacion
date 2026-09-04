<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Zonificacion\TblGrupo;
use App\Models\Zonificacion\TblLocalidadesMunicipio;
use App\Models\Zonificacion\TblInspectorDetalle;
use App\Models\Zonificacion\TblLocalidadesSede;
use App\Models\Zonificacion\TblBarrios;
use App\Models\Zonificacion\TblGruposDetalle;
use App\Models\Zonificacion\TblSubgrupo;
use App\Models\TblInspCali;
use App\Models\Programacion\TblProgramacionBase;


class Actualizar_zonificacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:actualizar_zonificacion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('tbl_inspector_detalle')->truncate();
        DB::table('tbl_grupos_detalle')->delete();
        // Usamos chunk para procesar en bloques y no saturar la memoria si son muchos registros
        // O puedes mantener el ::all() si son pocos.
        $base = TblProgramacionBase::all();

        // ... existing code ...
        foreach ($base as $item) {
            // Validar que al menos uno de los campos principales tenga datos
            if(empty($item->DESC_LOCALIDAD) && empty($item->GRUPO) && empty($item->SUB_GRUPO) && empty($item->BARRIO)){
                continue;
            }
            if(empty($item->ID_TECNICO)){
                continue;
            }
            // Usamos una transacción
            DB::beginTransaction();

            try {
                // 1. Limpieza de datos
                $nombreLocalidad = trim(preg_replace('/\s+/', ' ', $item->DESC_LOCALIDAD ?? ''));
                $nombreGrupo     = trim(preg_replace('/\s+/', ' ', $item->GRUPO ?? ''));
                $nombreSubgrupo  = trim(preg_replace('/\s+/', ' ', $item->SUB_GRUPO ?? ''));
                $nombreBarrio    = trim(preg_replace('/\s+/', ' ', $item->BARRIO ?? ''));

                // 2. Buscamos o creamos
                $localidad = !empty($nombreLocalidad)
                    ? TblLocalidadesMunicipio::firstOrCreate(['nombre' => $nombreLocalidad])
                    : null;

                $grupo = !empty($nombreGrupo)
                    ? TblGrupo::firstOrCreate(['grupo' => $nombreGrupo])
                    : null;

                $subgrupo = !empty($nombreSubgrupo)
                    ? TblSubgrupo::firstOrCreate(['subgrupo' => $nombreSubgrupo])
                    : null;

                $barrio = !empty($nombreBarrio)
                    ? TblBarrios::firstOrCreate(['barrio' => $nombreBarrio])
                    : null;

                // 3. Intento de inserción
                $detalle = TblGruposDetalle::firstOrCreate([
                    'id_mun'      => $localidad?->id,
                    'id_grupo'    => $grupo?->id,
                    'id_subGrupo' => $subgrupo?->id,
                    'id_barrio'   => $barrio?->id,
                ]);

                // Si todo sale bien, confirmamos la transacción

                TblInspectorDetalle::firstOrCreate([
                    'detalle_id' => $detalle->id,
                    'inspector_id' => $item->ID_TECNICO
                ]);

                DB::commit();

            } catch (\Exception $e) {
                // Si algo falla, revertimos la transacción actual
                DB::rollBack();

                $this->error("¡ERROR DETECTADO! Deteniendo ejecución...");

                // Imprimimos el estado de las variables
                $this->table(
                    ['Variable', 'Valor (ID)', 'Nombre Original'],
                    [
                        ['$localidad', $localidad?->id ?? 'NULL', $nombreLocalidad],
                        ['$grupo',     $grupo?->id     ?? 'NULL', $nombreGrupo],
                        ['$subgrupo',  $subgrupo?->id  ?? 'NULL', $nombreSubgrupo],
                        ['$barrio',    $barrio?->id    ?? 'NULL', $nombreBarrio],
                    ]
                );

                $this->error("Mensaje de SQL: " . $e->getMessage());

                // Opcional: Detener todo el script para que leas el error
                // return;
                // Opcional: Lanzar la excepción de nuevo si quieres ver el stack trace completo
                continue;
            }
        }
// ... existing code ...
    }

}
