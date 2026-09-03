{{-- ============================== MUNICIPIO ============================== --}}
<x-modal show="modal === 'municipio'" close="cerrar()" size="max-w-lg"
         icon="fa-city" tint="blue">
    <x-slot:titleSlot><span x-text="municipio.id ? 'Editar municipio' : 'Crear municipio'"></span></x-slot:titleSlot>

    <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
        <div>
            <label class="tw-label" for="nombreMunicipio">Nombre</label>
            {{-- En mayúsculas al escribir, como en la versión anterior. --}}
            <input type="text" id="nombreMunicipio" class="tw-input" :class="claseCampo('nombre')"
                   maxlength="100" x-model="municipio.nombre"
                   @input="municipio.nombre = $event.target.value.toUpperCase()">
        </div>

        <div>
            <label class="tw-label" for="sedeMunicipio">Sede</label>
            <select id="sedeMunicipio" class="tw-select" :class="claseCampo('sede')" x-model="municipio.id_sede">
                <option value="">Seleccione una sede</option>
                <template x-for="s in sedes" :key="s.id">
                    <option :value="s.id" x-text="s.nombre"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="tw-label" for="zonaMunicipio">Zona</label>
            <select id="zonaMunicipio" class="tw-select" :class="claseCampo('zona')" x-model="municipio.id_zona">
                <option value="">Seleccione una zona</option>
                <template x-for="z in zonas" :key="z.id">
                    <option :value="z.id" x-text="z.nombre"></option>
                </template>
            </select>
        </div>

        <p x-show="error" x-cloak
           class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                  dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
           x-text="error"></p>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
        <button type="button" class="tw-btn-primary" @click="guardarMunicipio()" :disabled="guardando">
            <i class="fas" :class="guardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
            <span x-text="municipio.id ? 'Guardar cambios' : 'Crear municipio'"></span>
        </button>
    </x-slot:footer>
</x-modal>

{{-- ================================ BARRIO =============================== --}}
<x-modal show="modal === 'barrio'" close="cerrar()" size="max-w-md"
         icon="fa-map-location-dot" tint="violet">
    <x-slot:titleSlot><span x-text="barrio.id ? 'Editar barrio' : 'Crear barrio'"></span></x-slot:titleSlot>

    <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
        <div>
            <label class="tw-label" for="nombreBarrio">Nombre</label>
            <input type="text" id="nombreBarrio" class="tw-input" :class="claseCampo('barrio')"
                   maxlength="255" x-model="barrio.barrio"
                   @input="barrio.barrio = $event.target.value.toUpperCase()">
            <p class="tw-hint">El municipio se asigna al relacionar el barrio con un grupo.</p>
        </div>

        <p x-show="error" x-cloak
           class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                  dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
           x-text="error"></p>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
        <button type="button" class="tw-btn-primary" @click="guardarBarrio()" :disabled="guardando">
            <i class="fas" :class="guardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
            <span x-text="barrio.id ? 'Guardar cambios' : 'Crear barrio'"></span>
        </button>
    </x-slot:footer>
</x-modal>

{{-- ============================ SEDES Y ZONAS ============================ --}}
{{-- Los formularios de sede y de zona no se apilan sobre esta ventana: la
     sustituyen y al cerrarse vuelven aquí. Dos modales a la vez pelean por el
     foco, porque cada uno monta su propio x-trap. --}}
<x-modal show="modal === 'sedes'" close="cerrar()" size="max-w-4xl"
         icon="fa-building" tint="slate"
         title="Gestionar sedes y zonas">

    <div class="grid gap-4 2xl:gap-5 px-4 py-4 2xl:px-5 2xl:py-5 md:grid-cols-2">

        @foreach ([
            ['clave' => 'sedes', 'titulo' => 'Sedes', 'singular' => 'sede', 'icono' => 'fa-building'],
            ['clave' => 'zonas', 'titulo' => 'Zonas', 'singular' => 'zona', 'icono' => 'fa-earth-americas'],
        ] as $bloque)
            <div class="rounded-2xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3
                            dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                        <i class="fas {{ $bloque['icono'] }} mr-1.5 text-slate-400"></i>{{ $bloque['titulo'] }}
                    </h3>
                    <button type="button" class="tw-btn-primary tw-btn-sm"
                            @click="abrir{{ ucfirst($bloque['singular']) }}()">
                        <i class="fas fa-plus"></i> Crear
                    </button>
                </div>

                <div class="max-h-72 overflow-auto">
                    <table class="tw-table tw-table-fija">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col" class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="r in {{ $bloque['clave'] }}" :key="r.id">
                                <tr>
                                    <td>
                                        <span class="font-medium text-slate-900 dark:text-white" x-text="r.nombre"></span>
                                        <span class="tw-badge ml-2" :class="r.activo ? 'chip-emerald' : 'chip-rose'"
                                              x-text="r.activo ? 'Activo' : 'Inactivo'"></span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="tw-btn-secondary tw-btn-sm"
                                                    @click="abrir{{ ucfirst($bloque['singular']) }}(r)">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button type="button" class="tw-btn-sm"
                                                    :class="r.activo ? 'tw-btn-danger' : 'tw-btn-primary'"
                                                    @click="cambiarEstado{{ ucfirst($bloque['singular']) }}(r)"
                                                    x-text="r.activo ? 'Desactivar' : 'Activar'"></button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="!{{ $bloque['clave'] }}.length">
                                <td colspan="2" class="px-4 py-8 text-center text-slate-500">
                                    Sin registros.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
    </x-slot:footer>
</x-modal>

{{-- ========================== FORMULARIO DE SEDE ========================= --}}
<x-modal show="modal === 'sede'" close="modal = 'sedes'" size="max-w-md"
         icon="fa-building" tint="slate">
    <x-slot:titleSlot><span x-text="sede.id ? 'Editar sede' : 'Crear sede'"></span></x-slot:titleSlot>

    <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
        <div>
            <label class="tw-label" for="nombreSede">Nombre</label>
            <input type="text" id="nombreSede" class="tw-input" :class="claseCampo('nombre')"
                   maxlength="255" x-model="sede.nombre">
        </div>

        <p x-show="error" x-cloak
           class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                  dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
           x-text="error"></p>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="modal = 'sedes'">Cerrar</button>
        <button type="button" class="tw-btn-primary" @click="guardarSede()" :disabled="guardando">
            <i class="fas" :class="guardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
            <span x-text="sede.id ? 'Guardar cambios' : 'Crear sede'"></span>
        </button>
    </x-slot:footer>
</x-modal>

{{-- ========================== FORMULARIO DE ZONA ========================= --}}
<x-modal show="modal === 'zona'" close="modal = 'sedes'" size="max-w-md"
         icon="fa-earth-americas" tint="emerald">
    <x-slot:titleSlot><span x-text="zona.id ? 'Editar zona' : 'Crear zona'"></span></x-slot:titleSlot>

    <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
        <div>
            <label class="tw-label" for="nombreZona">Nombre</label>
            <input type="text" id="nombreZona" class="tw-input" :class="claseCampo('nombre')"
                   maxlength="255" x-model="zona.nombre">
        </div>

        <p x-show="error" x-cloak
           class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                  dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
           x-text="error"></p>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="modal = 'sedes'">Cerrar</button>
        <button type="button" class="tw-btn-primary" @click="guardarZona()" :disabled="guardando">
            <i class="fas" :class="guardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
            <span x-text="zona.id ? 'Guardar cambios' : 'Crear zona'"></span>
        </button>
    </x-slot:footer>
</x-modal>

{{-- ====================== INSPECTORES DE LA RELACIÓN ===================== --}}
<x-modal show="modal === 'inspectores'" close="cerrar()" size="max-w-lg"
         icon="fa-users" tint="blue" title="Inspectores asignados">
    <x-slot:subtitle>
        <span x-text="detalle.grupo ? `Grupo ${detalle.grupo} · sub grupo ${detalle.subgrupo}` : ''"></span>
    </x-slot:subtitle>

    <div class="px-4 py-4 2xl:px-5 2xl:py-5">
        <div x-show="cargandoInspectores" x-cloak class="py-6 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-brand-600 dark:text-brand-300"></i>
        </div>

        <ul x-show="!cargandoInspectores" x-cloak class="divide-y divide-slate-100 dark:divide-slate-700">
            <template x-for="i in detalle.inspectores" :key="i.id">
                <li class="flex items-center gap-3 py-2.5">
                    <span class="tw-chip chip-blue h-8 w-8 text-xs" x-text="i.id"></span>
                    <span class="text-sm" x-text="`${i.apellidos} ${i.nombres}`"></span>
                </li>
            </template>
        </ul>

        <p x-show="!cargandoInspectores && !detalle.inspectores.length" x-cloak
           class="py-6 text-center text-sm text-slate-500">
            No hay inspectores asignados.
        </p>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
    </x-slot:footer>
</x-modal>
