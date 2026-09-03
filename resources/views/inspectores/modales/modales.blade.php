{{-- ============================ NUEVO INSPECTOR ========================= --}}
<x-modal show="modal === 'crear'" close="cerrar()" size="max-w-2xl"
         icon="fa-user-plus" tint="emerald" title="Nuevo inspector"
         subtitle="Los datos deben coincidir con movilidad">

    <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
        <div class="flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm
                    text-sky-900 dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-200">
            <i class="fas fa-circle-info mt-0.5"></i>
            <span>
                Coloca la información tal cual está en movilidad, para evitar errores
                en el cruce de datos entre las aplicaciones.
            </span>
        </div>

        <template x-if="error">
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                        dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                 x-text="error"></div>
        </template>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="tw-label" for="idCrear">ID</label>
                <input type="text" id="idCrear" class="tw-input" :class="claseCampo('idCrear')"
                       inputmode="numeric" x-model="form.id"
                       @input="form.id = soloDigitos($event.target.value)">
            </div>
            <div>
                <label class="tw-label" for="cedulaCrear">Identificación</label>
                <div class="flex gap-2">
                    <select id="typeIdCrear" class="tw-select w-24" :class="claseCampo('type_id')"
                            x-model="form.type_id">
                        <option value="">Tipo</option>
                        <option value="CC">CC</option>
                        <option value="CE">CE</option>
                    </select>
                    <input type="text" id="cedulaCrear" class="tw-input" :class="claseCampo('cedula')"
                           inputmode="numeric" x-model="form.cedula"
                           @input="form.cedula = soloDigitos($event.target.value)">
                </div>
            </div>

            <div>
                <label class="tw-label" for="nombresCrear">Nombres</label>
                <input type="text" id="nombresCrear" class="tw-input" :class="claseCampo('nombres')"
                       x-model="form.nombres"
                       @input="form.nombres = soloLetras($event.target.value)">
            </div>
            <div>
                <label class="tw-label" for="apellidosCrear">Apellidos</label>
                <input type="text" id="apellidosCrear" class="tw-input" :class="claseCampo('apellidos')"
                       x-model="form.apellidos"
                       @input="form.apellidos = soloLetras($event.target.value)">
            </div>

            <div class="sm:col-span-2">
                <label class="tw-label" for="supervisorCrear">Supervisor</label>
                <select id="supervisorCrear" class="tw-select" :class="claseCampo('supervisor')"
                        x-model="form.supervisor">
                    <option value="">Seleccione un supervisor</option>
                    <template x-for="s in supervisores" :key="s.id">
                        <option :value="s.id" x-text="s.nombre"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
        <button type="button" class="tw-btn-primary" :disabled="enviando" @click="guardarNuevo()">
            <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i> Guardar
        </button>
    </x-slot:footer>
</x-modal>

{{-- ============================ EDITAR INSPECTOR ======================== --}}
<x-modal show="modal === 'editar'" close="cerrar()" size="max-w-2xl"
         icon="fa-user-pen" tint="blue" title="Editar inspector"
         subtitle="El ID y la identificación no se pueden cambiar">

    <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
        <template x-if="error">
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                        dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                 x-text="error"></div>
        </template>

        <div x-show="cargandoFicha" x-cloak class="py-6 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-brand-600 dark:text-brand-300"></i>
        </div>

        <div class="grid gap-4 sm:grid-cols-2" x-show="!cargandoFicha" x-cloak>
            <div>
                <label class="tw-label" for="idEditar">ID</label>
                <input type="text" id="idEditar" class="tw-input" x-model="form.id" disabled>
            </div>
            <div>
                <label class="tw-label" for="cedulaEditar">Identificación</label>
                <div class="flex gap-2">
                    <input type="text" class="tw-input w-24 text-center" x-model="form.type_id" disabled>
                    <input type="text" id="cedulaEditar" class="tw-input" x-model="form.cedula" disabled>
                </div>
            </div>

            <div>
                <label class="tw-label" for="nombresEditar">Nombres</label>
                <input type="text" id="nombresEditar" class="tw-input" :class="claseCampo('nombres')"
                       x-model="form.nombres"
                       @input="form.nombres = soloLetras($event.target.value)">
            </div>
            <div>
                <label class="tw-label" for="apellidosEditar">Apellidos</label>
                <input type="text" id="apellidosEditar" class="tw-input" :class="claseCampo('apellidos')"
                       x-model="form.apellidos"
                       @input="form.apellidos = soloLetras($event.target.value)">
            </div>

            <div>
                <label class="tw-label" for="supervisorEditar">Supervisor</label>
                <select id="supervisorEditar" class="tw-select" :class="claseCampo('supervisor')"
                        x-model="form.supervisor">
                    <template x-for="s in supervisores" :key="s.id">
                        <option :value="s.id" x-text="s.nombre"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="tw-label" for="aprendizEditar">Aprendiz</label>
                <select id="aprendizEditar" class="tw-select" x-model="form.aprendiz">
                    <option value="0">No</option>
                    <option value="1">Sí</option>
                </select>
            </div>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
        <button type="button" class="tw-btn-primary" :disabled="enviando || cargandoFicha"
                @click="guardarEdicion()">
            <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i> Guardar cambios
        </button>
    </x-slot:footer>
</x-modal>

{{-- ========================= INSPECTORES DESACTIVADOS =================== --}}
<x-modal show="modal === 'desactivados'" close="cerrar()" size="max-w-5xl"
         icon="fa-user-slash" tint="slate" title="Inspectores desactivados"
         subtitle="Vuelve a activarlos cuando haga falta">

    <div class="px-4 py-4 2xl:px-5 2xl:py-5">
        <div class="relative mb-4 sm:max-w-sm" x-show="!cargandoDesactivados && desactivados.length > 0"
             x-cloak>
            <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2
                      text-sm text-slate-400"></i>
            <input type="search" class="tw-input pl-9" placeholder="Buscar por nombre, cédula…"
                   x-model="busquedaDesactivados">
            <p class="tw-hint">
                <span x-text="desactivadosFiltrados.length"></span> de
                <span x-text="desactivados.length"></span> desactivados
            </p>
        </div>

        <div x-show="cargandoDesactivados" x-cloak class="py-10 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-brand-600 dark:text-brand-300"></i>
        </div>

        <div x-show="!cargandoDesactivados && desactivados.length === 0" x-cloak
             class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
            <i class="fas fa-user-check mb-2 block text-2xl opacity-40"></i>
            No hay inspectores desactivados.
        </div>

        <div x-show="!cargandoDesactivados && desactivados.length > 0 && desactivadosFiltrados.length === 0"
             x-cloak class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
            <i class="fas fa-magnifying-glass mb-2 block text-2xl opacity-40"></i>
            Ningún inspector desactivado coincide con la búsqueda.
        </div>

        <div class="overflow-x-auto" x-show="!cargandoDesactivados && desactivadosFiltrados.length > 0"
             x-cloak>
            <table class="tw-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Identificación</th>
                    <th>Supervisor</th>
                    <th class="text-right">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <template x-for="fila in desactivadosFiltrados" :key="fila.id">
                    <tr>
                        <td class="font-mono text-xs text-slate-500 dark:text-slate-400" x-text="fila.id"></td>
                        <td class="font-medium text-slate-800 dark:text-slate-100" x-text="fila.nombres"></td>
                        <td x-text="fila.apellidos"></td>
                        <td class="whitespace-nowrap">
                            <span class="text-slate-400" x-text="fila.type_id"></span>
                            <span x-text="fila.cedula"></span>
                        </td>
                        <td x-text="fila.supervisor"></td>
                        <td class="text-right">
                            <button type="button" class="tw-btn-primary tw-btn-sm"
                                    @click="cambiarEstado(fila, true)">
                                <i class="fas fa-user-check"></i> Activar
                            </button>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
    </x-slot:footer>
</x-modal>
