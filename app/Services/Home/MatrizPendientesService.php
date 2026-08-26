<?php

namespace App\Services\Home;

use App\Models\Programacion\tbl_programacion_base;
use Illuminate\Support\Collection;

class MatrizPendientesService
{
    public function __construct(
        private LimpiezaMunicipioService $municipios
    ) {}

    /**
     * Matriz de pendientes: cantidad de contratos sin recepcionar por localidad y tipo de trabajo.
     *
     * @return Collection Registros con DESC_LOCALIDAD, criterio, cantidad y MUNICIPIO_MADRE.
     */
    public function obtener(): Collection
    {
        $datos_matriz = tbl_programacion_base::where('ESTADO_RECEPCION', '!=', '1')
            ->selectRaw('DESC_LOCALIDAD, ID_TIPO_TRABAJO as criterio, COUNT(*) as cantidad')
            ->groupBy('DESC_LOCALIDAD')
            ->groupByRaw('ID_TIPO_TRABAJO')
            ->get();

        foreach ($datos_matriz as $item) {
            $item->MUNICIPIO_MADRE = $this->municipios->limpiar($item->DESC_LOCALIDAD);
        }

        return $datos_matriz;
    }
}
