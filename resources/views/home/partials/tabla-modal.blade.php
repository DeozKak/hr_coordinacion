{{-- Tabla de detalle dentro de un modal.
     Lee el estado directamente del componente `dashboard` (tabla*, filas*):
     solo hay un modal abierto a la vez, así que comparten estado.
     $columnas = ['clave' => 'Encabezado']. --}}
<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 px-5 py-3 dark:border-slate-700/60">
    <label class="relative w-full sm:max-w-xs">
        <span class="sr-only">Buscar</span>
        <i class="fas fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="search" x-model.debounce.200ms="tablaBusqueda" placeholder="Buscar…" class="tw-input py-2 pl-10">
    </label>
    <span class="text-sm text-slate-500" x-text="`${filasFiltradas.length} registros`"></span>
</div>

<table class="tw-table">
    <thead class="sticky top-0 z-10">
        <tr>
            @foreach ($columnas as $clave => $encabezado)
                <th>
                    <button type="button" @click="ordenarPor('{{ $clave }}')"
                            class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">
                        {{ $encabezado }}
                        <i class="fas text-[10px]"
                           :class="tablaOrden.key === '{{ $clave }}'
                               ? (tablaOrden.dir === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down')
                               : 'fa-sort opacity-30'"></i>
                    </button>
                </th>
            @endforeach
            @if ($estado ?? false)<th>Estado</th>@endif
        </tr>
    </thead>

    <tbody>
        <template x-for="(fila, i) in filasPaginadas" :key="i">
            <tr>
                @foreach ($columnas as $clave => $encabezado)
                    <td>
                        @if (($pill ?? null) === $clave)
                            <span class="pill-slate" x-text="fila.{{ $clave }} ?? '—'"></span>
                        @else
                            <span x-text="fila.{{ $clave }} ?? '—'"></span>
                        @endif
                    </td>
                @endforeach

                @if ($estado ?? false)
                    <td>
                        <span :class="progEstado === 'ejecutadas' ? 'pill-emerald' : 'pill-rose'">
                            <i class="fas text-[10px]" :class="progEstado === 'ejecutadas' ? 'fa-circle-check' : 'fa-clock'"></i>
                            <span x-text="progEstado === 'ejecutadas' ? 'Ejecutada' : 'Pendiente'"></span>
                        </span>
                    </td>
                @endif
            </tr>
        </template>

        <tr x-show="!filasFiltradas.length">
            <td colspan="{{ count($columnas) + (($estado ?? false) ? 1 : 0) }}" class="py-12 text-center text-slate-400">
                No se encontraron registros.
            </td>
        </tr>
    </tbody>
</table>

<div class="flex items-center justify-between gap-3 border-t border-slate-200/80 px-5 py-3 text-sm dark:border-slate-700/60"
     x-show="tablaTotalPaginas > 1">
    <span class="text-slate-500" x-text="`Página ${tablaPagina} de ${tablaTotalPaginas}`"></span>
    <div class="flex gap-2">
        <button type="button" class="tw-btn-secondary tw-btn-sm" @click="tablaPagina--" :disabled="tablaPagina === 1">Anterior</button>
        <button type="button" class="tw-btn-secondary tw-btn-sm" @click="tablaPagina++" :disabled="tablaPagina === tablaTotalPaginas">Siguiente</button>
    </div>
</div>
