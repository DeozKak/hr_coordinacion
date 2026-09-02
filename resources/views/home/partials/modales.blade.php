{{-- ============ DETALLE DEL REPORTE DIARIO ============ --}}
<x-modal show="modal === 'detalle'" icon="fa-list" tint="blue" size="max-w-6xl">
    <x-slot:title-slot>
        <span x-text="detalleTitulo"></span>
    </x-slot:title-slot>
    <x-slot:subtitle>Detalle de la operación del día</x-slot:subtitle>

        @include('home.partials.tabla-modal', [
            'columnas' => [
                'contrato'  => 'Contrato / Sitio',
                'operario'  => 'Nombre operario',
                'localidad' => 'Municipio',
                'tarea'     => 'Tipo tarea',
                'cierre'    => 'Cierre',
                'fecha'     => 'Fecha ejecución',
            ],
            'pill' => 'cierre',
        ])
</x-modal>

{{-- ============ DETALLE DE PROGRAMACIONES ============ --}}
<x-modal show="modal === 'programacion'" icon="fa-clipboard-list" tint="amber" size="max-w-6xl">
    <x-slot:title-slot>
        <span x-text="progTitulo"></span>
    </x-slot:title-slot>
    <x-slot:subtitle>Programaciones del día por tipo de trabajo</x-slot:subtitle>

        @include('home.partials.tabla-modal', [
            'columnas' => [
                'contrato' => 'Contrato',
                'orden'    => 'Ordenlist',
                'cliente'  => 'Cliente',
                'tecnico'  => 'Técnico asignado',
                'ciudad'   => 'Municipio',
            ],
            'estado' => true,
        ])
</x-modal>

{{-- ============ TÉCNICOS DE UNA LOCALIDAD ============ --}}
<x-modal show="modal === 'tecnicos'" icon="fa-location-dot" tint="violet" size="max-w-lg">
    <x-slot:title-slot>
        <span x-text="`Técnicos en ${tecnicosLocalidad}`"></span>
    </x-slot:title-slot>
    <x-slot:subtitle>
        <span x-text="`${tecnicosLista.length} ${tecnicosLista.length === 1 ? 'técnico asignado' : 'técnicos asignados'}`"></span>
    </x-slot:subtitle>

    <ul class="divide-y divide-slate-100 dark:divide-slate-700">
        <template x-for="t in tecnicosLista" :key="t.id">
            <li class="flex items-start gap-3 px-5 py-3.5">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/60 dark:text-violet-200">
                    <i class="fas fa-user"></i>
                </span>
                <div class="min-w-0">
                    <p class="truncate font-medium text-slate-800 dark:text-slate-200" x-text="t.nombre"></p>
                    <p class="text-xs text-slate-500" x-text="`Supervisor: ${t.supervisor}`"></p>
                    <p class="text-xs text-slate-400" x-text="`Código ID: ${t.id}`"></p>
                </div>
            </li>
        </template>
    </ul>
</x-modal>

{{-- ============ ASIGNAR TÉCNICOS ============ --}}
@can('ver_coordinacion_RP')
<x-modal show="modal === 'asignacion'" icon="fa-user-plus" tint="blue" size="max-w-3xl"
         title="Asignar Técnicos a Localidad">
    <x-slot:subtitle>Puede seleccionar varios técnicos a la vez</x-slot:subtitle>

    <form action="{{ route('asignacion.guardar_tecnicos') }}" method="POST" id="formAsignacion">
        @csrf
        <div class="space-y-5 p-5">
            <div>
                <label for="localidad_input" class="tw-label">Localidad / Municipio</label>
                <input type="text" name="localidad" id="localidad_input" class="tw-input" required
                       placeholder="Ej: CALI, PALMIRA, CANDELARIA..."
                       x-model="asigLocalidad"
                       @input="asigLocalidad = asigLocalidad.toUpperCase()">
                <p class="tw-hint">
                    Si escribe una localidad existente, se sumarán a ella. Si no existe, se creará una nueva.
                </p>
            </div>

            <div>
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <span class="tw-label mb-0">Técnicos</span>
                    <span class="pill-blue" x-text="`${asigSeleccionados.length} seleccionados`"></span>
                </div>
                <p class="tw-hint mb-2 mt-0">
                    <i class="fas fa-circle-info"></i>
                    Puede marcar técnicos de otra localidad: al guardar se trasladan aquí automáticamente.
                </p>

                <input type="search" class="tw-input mb-3" placeholder="Buscar técnico por nombre o ID…"
                       x-model.debounce.150ms="asigBusqueda">

                <div class="max-h-[280px] overflow-auto rounded-xl border border-slate-200 dark:border-slate-700">
                    <template x-for="t in tecnicosFiltrados" :key="t.id">
                        <label class="flex cursor-pointer items-start gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0
                                      hover:bg-slate-50 dark:border-slate-700/50 dark:hover:bg-slate-700/40">
                            <input type="checkbox" name="tecnicos[]" :value="t.id" x-model.number="asigSeleccionados"
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-slate-800 dark:text-slate-200" x-text="t.nombre"></span>
                                <span class="block text-xs text-slate-400" x-text="`ID: ${t.id}`"></span>
                                <span x-show="t.asignado_en && t.asignado_en !== asigLocalidad" class="pill-amber mt-1">
                                    <i class="fas fa-location-dot text-[10px]"></i>
                                    <span x-text="`Actualmente en: ${t.asignado_en}`"></span>
                                </span>
                            </span>
                        </label>
                    </template>

                    <p x-show="!tecnicosFiltrados.length" class="py-10 text-center text-sm text-slate-400">
                        Ningún técnico coincide con la búsqueda.
                    </p>
                </div>
            </div>
        </div>
    </form>

    <x-slot:footer>
        <button type="button" @click="modal = null" class="tw-btn-secondary">Cancelar</button>
        <button type="submit" form="formAsignacion" class="tw-btn-primary">
            <i class="fas fa-floppy-disk"></i> Guardar asignación
        </button>
    </x-slot:footer>
</x-modal>
@endcan

{{-- ============ CARGAR ARCHIVOS OT ============ --}}
@haspermission('ver_residente')
<x-modal show="modal === 'cargue'" icon="fa-file-excel" tint="emerald" size="max-w-xl"
         title="Cargar Archivos OT">
    <x-slot:subtitle>Formatos Excel (.xlsx, .xls) o CSV</x-slot:subtitle>

    <form action="{{ route('insercion_estadisticas_asignacion') }}" method="POST"
          enctype="multipart/form-data" id="formCargue" @submit="validarCargue($event)">
        @csrf
        <div class="space-y-4 p-5">
            <p class="text-sm text-slate-500">
                Seleccione ambos archivos para actualizar la base de datos.
            </p>

            @foreach ([
                'archivo_asignacion' => 'Archivo OT ABIERTAS (Asignación)',
                'archivo_cerradas'   => 'Archivo OT CERRADAS',
            ] as $campo => $etiqueta)
                <div>
                    <span class="tw-label">{{ $etiqueta }}</span>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-4 py-4
                                  transition hover:border-brand-400 hover:bg-brand-50/40 dark:border-slate-600 dark:hover:bg-slate-700/40">
                        <span class="tw-chip chip-emerald"><i class="fas fa-file-arrow-up"></i></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-slate-700 dark:text-slate-200"
                                  x-text="archivos.{{ $campo }} || 'Seleccionar archivo…'"></span>
                            <span class="block text-xs text-slate-400">.xlsx, .xls o .csv</span>
                        </span>
                        <input type="file" name="{{ $campo }}" class="sr-only"
                               accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                               @change="archivos.{{ $campo }} = $event.target.files[0]?.name ?? ''">
                    </label>
                </div>
            @endforeach
        </div>
    </form>

    <x-slot:footer>
        <button type="button" @click="modal = null" class="tw-btn-secondary">Cancelar</button>
        <button type="submit" form="formCargue" class="tw-btn-primary" :disabled="subiendo">
            <i class="fas" :class="subiendo ? 'fa-spinner fa-spin' : 'fa-cloud-arrow-up'"></i>
            <span x-text="subiendo ? 'Procesando, espere…' : 'Subir e importar'"></span>
        </button>
    </x-slot:footer>
</x-modal>
@endhaspermission
