{{-- =============================== CORTE ============================== --}}
<x-modal show="modal === 'corte'" close="cerrar()" size="max-w-xl"
         icon="fa-calendar-days" tint="blue">
    <x-slot:titleSlot><span x-text="editandoCorte ? 'Editar corte' : 'Crear corte'"></span></x-slot:titleSlot>
    <x-slot:subtitle>Periodo, meta y umbral de dobles</x-slot:subtitle>

    <div class="space-y-4 px-5 py-5">
        <template x-if="error">
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                        dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                 x-text="error"></div>
        </template>

        <div x-show="cargandoFicha" x-cloak class="py-6 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-brand-600 dark:text-brand-300"></i>
        </div>

        <div class="grid gap-4 sm:grid-cols-2" x-show="!cargandoFicha" x-cloak>
            <div class="sm:col-span-2">
                <label class="tw-label" for="nombreCorte">Nombre</label>
                <input type="text" id="nombreCorte" class="tw-input" :class="claseCampo('nombre')"
                       maxlength="255" x-model="corte.nombre">
            </div>

            <div>
                <label class="tw-label" for="fecha_inicio">Fecha de inicio</label>
                <input type="date" id="fecha_inicio" class="tw-input" :class="claseCampo('fecha_inicio')"
                       x-model="corte.fecha_inicio" :max="corte.fecha_fin || null">
            </div>
            <div>
                <label class="tw-label" for="fecha_fin">Fecha de fin</label>
                <input type="date" id="fecha_fin" class="tw-input" :class="claseCampo('fecha_fin')"
                       x-model="corte.fecha_fin" :min="corte.fecha_inicio || null">
            </div>

            <div>
                <label class="tw-label" for="meta">Meta</label>
                <input type="text" id="meta" class="tw-input" :class="claseCampo('meta')"
                       inputmode="numeric" x-model="corte.meta"
                       @input="corte.meta = soloDigitos($event.target.value, 3)">
                <p class="tw-hint">Hasta 250.</p>
            </div>
            <div>
                <label class="tw-label" for="dobles">Umbral de dobles del sábado</label>
                <input type="text" id="dobles" class="tw-input" :class="claseCampo('dobles')"
                       inputmode="numeric" x-model="corte.dobles"
                       @input="corte.dobles = soloDigitos($event.target.value, 2)">
                <p class="tw-hint">Hasta 50.</p>
            </div>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
        <button type="button" class="tw-btn-primary" :disabled="enviando || cargandoFicha"
                @click="guardarCorte()">
            <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
            <span x-text="editandoCorte ? 'Guardar cambios' : 'Crear corte'"></span>
        </button>
    </x-slot:footer>
</x-modal>

{{-- =============================== CAUSAL ============================= --}}
<x-modal show="modal === 'causal'" close="cerrar()" size="max-w-lg"
         icon="fa-rotate-left" tint="amber">
    <x-slot:titleSlot><span x-text="editandoCausal ? 'Editar causal' : 'Crear causal'"></span></x-slot:titleSlot>
    <x-slot:subtitle>Causal de devolución</x-slot:subtitle>

    <div class="space-y-4 px-5 py-5">
        <template x-if="error">
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                        dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                 x-text="error"></div>
        </template>

        <div x-show="cargandoFicha" x-cloak class="py-6 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-brand-600 dark:text-brand-300"></i>
        </div>

        <div x-show="!cargandoFicha" x-cloak>
            <label class="tw-label" for="nombreCausal">Nombre</label>
            <input type="text" id="nombreCausal" class="tw-input" :class="claseCampo('nombre')"
                   maxlength="255" x-model="causal.nombre">
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
        <button type="button" class="tw-btn-primary" :disabled="enviando || cargandoFicha"
                @click="guardarCausal()">
            <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
            <span x-text="editandoCausal ? 'Guardar cambios' : 'Crear causal'"></span>
        </button>
    </x-slot:footer>
</x-modal>
