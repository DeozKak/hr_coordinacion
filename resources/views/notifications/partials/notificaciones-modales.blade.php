{{-- ====================== EDITAR NOTIFICACIONES ========================= --}}
<x-modal show="modal === 'editar'" close="cerrar()" size="max-w-2xl"
         icon="fa-envelope-open-text" tint="blue"
         title="Notificaciones del usuario">

    <div class="space-y-4 2xl:space-y-5 px-4 py-4 2xl:px-5 2xl:py-5">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="editando?.name"></span>
            <span x-text="editando ? ` · ${editando.email}` : ''"></span>
        </p>

        {{-- Doble lista: misma forma que la de permisos en gestión de usuarios. --}}
        <div>
            <span class="tw-label">Notificaciones</span>
            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                <div>
                    <label for="dispNotif" class="mb-1 block text-xs text-slate-500">Disponibles</label>
                    <select id="dispNotif" multiple size="8" x-model="disponiblesSel" class="tw-input">
                        <template x-for="n in disponibles" :key="n">
                            <option :value="n" x-text="n"></option>
                        </template>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <button type="button" class="tw-btn-secondary px-2 py-1"
                            :disabled="!disponiblesSel.length"
                            @click="mover('disponibles', 'asignadas', 'disponiblesSel')"
                            aria-label="Asignar notificaciones seleccionadas">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="button" class="tw-btn-secondary px-2 py-1"
                            :disabled="!asignadasSel.length"
                            @click="mover('asignadas', 'disponibles', 'asignadasSel')"
                            aria-label="Quitar notificaciones seleccionadas">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                </div>

                <div>
                    <label for="asigNotif" class="mb-1 block text-xs text-slate-500">Asignadas</label>
                    <select id="asigNotif" multiple size="8" x-model="asignadasSel" class="tw-input">
                        <template x-for="n in asignadas" :key="n">
                            <option :value="n" x-text="n"></option>
                        </template>
                    </select>
                </div>
            </div>

            <p x-show="cargando" x-cloak class="mt-2 text-xs text-slate-500">
                <i class="fas fa-circle-notch fa-spin"></i> Cargando notificaciones…
            </p>
        </div>

        <p x-show="error" x-cloak
           class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-200"
           x-text="error"></p>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cancelar</button>
        <button type="button" class="tw-btn-primary" @click="guardar()" :disabled="guardando || cargando">
            <i class="fas" :class="guardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i> Guardar
        </button>
    </x-slot:footer>
</x-modal>

{{-- ======================== CREAR NOTIFICACIÓN =========================== --}}
<x-modal show="modal === 'crear'" close="cerrar()" size="max-w-md"
         icon="fa-plus" tint="emerald"
         title="Crear notificación">

    <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
        <div>
            <label class="tw-label" for="nombreNotificacion">Nombre</label>
            <input type="text" id="nombreNotificacion" class="tw-input"
                   :class="error ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : ''"
                   x-model="nuevaNotificacion" @keydown.enter.prevent="crear()"
                   placeholder="Ej. CIERRE DE CORTE" autocomplete="off">
            <p class="tw-hint">
                Sólo crea el aviso. Para que alguien lo reciba hay que asignárselo desde su ficha.
            </p>
        </div>

        <p x-show="error" x-cloak
           class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-200"
           x-text="error"></p>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cancelar</button>
        <button type="button" class="tw-btn-primary" @click="crear()" :disabled="guardando">
            <i class="fas" :class="guardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i> Guardar
        </button>
    </x-slot:footer>
</x-modal>
