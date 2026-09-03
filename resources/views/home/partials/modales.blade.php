{{-- ============ DETALLE DEL REPORTE DIARIO ============ --}}
<x-modal show="modal === 'detalle'" icon="fa-list" tint="blue" size="max-w-6xl">
    <x-slot:title-slot>
        <span x-text="detalleTitulo"></span>
    </x-slot:title-slot>
    <x-slot:subtitle>Detalle de la operación del día</x-slot:subtitle>

        {{-- "Meses" va pegada al contrato porque es un dato suyo, no de la
             ejecución. El servidor ya la manda en cada fila del detalle
             (infoModal en MetricasDiariasService y PendientesLegalizarService),
             así que aparece en todas estas ventanas; en la de inspectores sale
             vacía, igual que el contrato y la tarea, porque esas filas son un
             recuento de personas y no de contratos. --}}
        @include('home.partials.tabla-modal', [
            'columnas' => [
                'contrato'  => 'Contrato / Sitio',
                'operario'  => 'Nombre operario',
                'localidad' => 'Municipio',
                'tarea'     => 'Tipo tarea',
                 'meses'     => 'Meses',
                'cierre'    => 'Cierre',
                'fecha'     => 'Fecha ejecución',
            ],
            'pill' => 'cierre',
            'numericas' => ['meses'],
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
<x-modal show="modal === 'asignacion'" icon="fa-user-plus" tint="blue" size="max-w-5xl"
         title="Asignar Técnicos a Localidad">
    <x-slot:subtitle>Puede seleccionar varios técnicos a la vez</x-slot:subtitle>

    {{-- El envío lo hace Alpine para no recargar la página entera. `action` y
         `method` se dejan puestos a propósito: si el JS no llegara a cargar,
         @submit.prevent no se registra y el formulario se envía como siempre,
         que es el camino que el controlador sigue respondiendo con un redirect. --}}
    <form action="{{ route('asignacion.guardar_tecnicos') }}" method="POST" id="formAsignacion"
          @submit.prevent="guardarAsignacion()">
        @csrf
        <div class="space-y-4 2xl:space-y-5 p-4 2xl:p-5">
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

                {{-- Dos columnas a partir de sm: con 150 técnicos, una sola
                     obligaba a desplazar mucho para marcar unos pocos.

                     Las líneas de separación no son bordes de cada fila: son
                     el fondo del contenedor asomando por un hueco de 1px entre
                     celdas. Con dos columnas los bordes por elemento no salen
                     —no hay forma fiable de saber quién cierra fila, y el
                     <template> de x-for cuenta como hijo y desbarata cualquier
                     :nth-child—, mientras que el hueco se dibuja solo. --}}
                <div class="grid max-h-[17.5rem] gap-px overflow-auto rounded-xl border border-slate-200
                            bg-slate-100 dark:border-slate-700 dark:bg-slate-700/50 sm:grid-cols-2">
                    <template x-for="t in tecnicosFiltrados" :key="t.id">
                        <label class="flex cursor-pointer items-start gap-3 bg-white px-4 py-3
                                      hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700/40">
                            <input type="checkbox" name="tecnicos[]" :value="t.id" x-model.number="asigSeleccionados"
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-slate-800 dark:text-slate-200" x-text="t.nombre"></span>
                                <span class="block text-xs text-slate-400" x-text="`ID: ${t.id}`"></span>
                                <span x-show="t.asignado_en && t.asignado_en !== asigLocalidad" class="pill-amber mt-1">
                                    <i class="fas fa-location-dot text-[0.625rem]"></i>
                                    <span x-text="`Actualmente en: ${t.asignado_en}`"></span>
                                </span>
                            </span>
                        </label>
                    </template>

                    {{-- Relleno para el hueco que deja una lista impar: sin él
                         se vería el fondo del contenedor como un bloque gris. --}}
                    <div x-show="tecnicosFiltrados.length % 2 === 1"
                         class="hidden bg-white dark:bg-slate-800 sm:block"></div>

                    <p x-show="!tecnicosFiltrados.length"
                       class="col-span-full bg-white py-10 text-center text-sm text-slate-400 dark:bg-slate-800">
                        Ningún técnico coincide con la búsqueda.
                    </p>
                </div>
            </div>
        </div>
    </form>

    <x-slot:footer>
        <button type="button" @click="modal = null" class="tw-btn-secondary"
                :disabled="asigGuardando">Cancelar</button>
        <button type="submit" form="formAsignacion" class="tw-btn-primary" :disabled="asigGuardando">
            <i class="fas" :class="asigGuardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
            <span x-text="asigGuardando ? 'Guardando…' : 'Guardar asignación'"></span>
        </button>
    </x-slot:footer>
</x-modal>
@endcan

{{-- ============ CORTE DE GDO ============ --}}
<x-modal show="modal === 'corte'" icon="fa-scissors" tint="violet" size="max-w-lg"
         title="Corte de GDO">
    <x-slot:subtitle>Periodo sobre el que se mide la legalización</x-slot:subtitle>

    <form @submit.prevent="guardarCorte()" id="formCorte" class="space-y-4 p-4 2xl:p-5">

        {{-- Corregir el corte de ahora o abrir el siguiente. Sólo aparece si ya
             hay alguno: con la tabla vacía lo único posible es crear. --}}
        <template x-if="corte">
            <div class="flex rounded-xl bg-slate-100 p-1 dark:bg-slate-900/50" role="tablist">
                <button type="button" role="tab" @click="modoCorte(true)"
                        :aria-selected="corteEditando"
                        class="flex-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                        :class="corteEditando
                            ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                            : 'text-slate-500 hover:text-slate-700'">Corregir el actual</button>
                <button type="button" role="tab" @click="modoCorte(false)"
                        :aria-selected="!corteEditando"
                        class="flex-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                        :class="!corteEditando
                            ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                            : 'text-slate-500 hover:text-slate-700'">Crear uno nuevo</button>
            </div>
        </template>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="corte_inicio" class="tw-label">Fecha de inicio</label>
                <input type="date" id="corte_inicio" class="tw-input" x-model="corteForm.inicio" required>
            </div>
            <div>
                <label for="corte_fin" class="tw-label">Fecha de fin</label>
                {{-- El fin no puede quedar antes del inicio: el navegador lo
                     impide y el servidor lo vuelve a comprobar. --}}
                <input type="date" id="corte_fin" class="tw-input" x-model="corteForm.fin"
                       :min="corteForm.inicio" required>
            </div>
        </div>

        <p class="tw-hint mt-0">
            <i class="fas fa-circle-info"></i>
            Lo legalizado se cuenta por su fecha de legalización dentro del periodo.
            Los acumulados de pendientes y prioridades arrancan en la fecha de inicio.
        </p>

        <div x-show="corteError" x-cloak x-transition
             class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800
                    dark:border-red-800 dark:bg-red-950 dark:text-red-200"
             role="alert" x-text="corteError"></div>

        {{-- Historial breve: el corte anterior sigue siendo la referencia de lo
             que se reportó en su momento, así que conviene tenerlo a la vista. --}}
        <template x-if="corte && !corteEditando">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                El corte vigente
                <span class="font-medium" x-text="`${corte.inicio_mostrado} → ${corte.fin_mostrado}`"></span>
                se conserva; este se guarda aparte.
            </p>
        </template>
    </form>

    <x-slot:footer>
        <button type="button" @click="modal = null" class="tw-btn-secondary"
                :disabled="corteGuardando">Cancelar</button>
        <button type="submit" form="formCorte" class="tw-btn-primary" :disabled="corteGuardando">
            <i class="fas" :class="corteGuardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
            <span x-text="corteGuardando ? 'Guardando…' : (corteEditando ? 'Guardar cambios' : 'Crear corte')"></span>
        </button>
    </x-slot:footer>
</x-modal>

{{-- ============ LEGALIZADO EN EL CORTE ============ --}}
<x-modal show="modal === 'legalizado'" icon="fa-file-circle-check" tint="violet" size="max-w-6xl">
    <x-slot:title-slot>
        <span x-text="detalleTitulo"></span>
    </x-slot:title-slot>
    <x-slot:subtitle>Órdenes legalizadas dentro del corte de GDO</x-slot:subtitle>

        @include('home.partials.tabla-modal', [
            'columnas' => [
                'contrato'  => 'Contrato',
                'orden'     => 'Número orden',
                'operario'  => 'Técnico',
                'tarea'     => 'Tipo trabajo',
                'localidad' => 'Municipio',
                'causal'    => 'Causal',
                'fecha'     => 'Fecha legalización',
            ],
        ])
</x-modal>

{{-- ============ CARGAR ARCHIVOS OT ============ --}}
@haspermission('ver_residente')
<x-modal show="modal === 'cargue'" icon="fa-file-excel" tint="emerald" size="max-w-xl"
         title="Cargar Archivos OT">
    <x-slot:subtitle>Formatos Excel (.xlsx, .xls) o CSV</x-slot:subtitle>

    <form action="{{ route('insercion_estadisticas_asignacion') }}" method="POST"
          enctype="multipart/form-data" id="formCargue" @submit="validarCargue($event)">
        @csrf
        <div class="space-y-4 p-4 2xl:p-5">
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
