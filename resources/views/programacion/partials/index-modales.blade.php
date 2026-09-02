{{-- Los tres cargues comparten forma: archivo + errores + botón. --}}

<x-modal show="modal === 'base'" close="cerrarModal()" size="max-w-lg"
         icon="fa-database" tint="blue" title="Añadir a base"
         subtitle="Carga el Excel con la base de contratos">
    <div class="space-y-4 px-5 py-5">
        <template x-if="errores.length">
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                        dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200">
                <ul class="list-inside list-disc space-y-1">
                    <template x-for="(e, i) in errores" :key="i"><li x-text="e"></li></template>
                </ul>
            </div>
        </template>

        <x-file-input label="Archivo" ref="archivoBase" model="nombreBase" tint="blue"
                      hint="Excel (.xls o .xlsx)" accept=".xls,.xlsx" />

        <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300">
            <input type="checkbox" x-model="estado5"
                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500
                          dark:border-slate-600 dark:bg-slate-700">
            Cargue estado 5
        </label>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrarModal()">Cancelar</button>
        <button type="button" class="tw-btn-primary" :disabled="enviando" @click="subirBase()">
            <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-upload'"></i> Subir
        </button>
    </x-slot:footer>
</x-modal>

<x-modal show="modal === 'tecnicos'" close="cerrarModal()" size="max-w-lg"
         icon="fa-helmet-safety" tint="amber" title="Programadas técnicos"
         subtitle="Carga el Excel de programadas de técnicos">
    <div class="space-y-4 px-5 py-5">
        <template x-if="errores.length">
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                        dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200">
                <ul class="list-inside list-disc space-y-1">
                    <template x-for="(e, i) in errores" :key="i"><li x-text="e"></li></template>
                </ul>
            </div>
        </template>

        <x-file-input label="Archivo" ref="archivoTecnicos" model="nombreTecnicos" tint="amber"
                      hint="Excel (.xls o .xlsx)" accept=".xls,.xlsx" />
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrarModal()">Cancelar</button>
        <button type="button" class="tw-btn-primary" :disabled="enviando" @click="subirTecnicos()">
            <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-upload'"></i> Cargar
        </button>
    </x-slot:footer>
</x-modal>

<x-modal show="modal === 'gdo'" close="cerrarModal()" size="max-w-lg"
         icon="fa-headset" tint="violet" title="Programadas GDO"
         subtitle="El archivo se procesa en segundo plano">
    <div class="space-y-4 px-5 py-5">
        <template x-if="errores.length">
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                        dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200">
                <ul class="list-inside list-disc space-y-1">
                    <template x-for="(e, i) in errores" :key="i"><li x-text="e"></li></template>
                </ul>
            </div>
        </template>

        <x-file-input label="Archivo" ref="archivoGdo" model="nombreGdo" tint="violet"
                      hint="Excel (.xls o .xlsx)" accept=".xls,.xlsx" />
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrarModal()">Cancelar</button>
        <button type="button" class="tw-btn-primary" :disabled="enviando" @click="subirGdo()">
            <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-upload'"></i> Cargar
        </button>
    </x-slot:footer>
</x-modal>
