{{-- =====================================================================
     Modales de Coordinación de Quejas.
     Viven dentro del x-data="coordinacionPqrs(...)" de coordinacion.blade.php.
     ===================================================================== --}}

{{-- ============================== CARGAR DATOS ========================== --}}
<x-modal show="modal === 'cargar'" close="cerrar()" size="max-w-lg"
         title="Cargar quejas" icon="fa-file-arrow-up" tint="blue">
    <x-slot:subtitle>Bases OSF y soportes HTML</x-slot:subtitle>

    <form @submit.prevent="enviarCargar()" enctype="multipart/form-data">
        <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
            <x-file-input label="Quejas asignadas" hint="Excel .xlsx o .xls" tint="sky"
                          icon="fa-inbox" ref="asignadas" model="cargar.nombres.asignadas"
                          accept=".xlsx,.xls" />

            <x-file-input label="Quejas cerradas" hint="Excel .xlsx o .xls" tint="emerald"
                          icon="fa-check-double" ref="cerradas" model="cargar.nombres.cerradas"
                          accept=".xlsx,.xls" />

            <x-file-input label="Soportes HTML" hint="Puedes seleccionar varios .html" tint="amber"
                          icon="fa-code" ref="html" model="cargar.nombres.html"
                          multiple accept=".html" />

            <div x-show="cargar.enviando" x-cloak
                 class="rounded-xl border border-brand-100 bg-brand-50/70 px-4 py-5 text-center
                        dark:border-brand-900/50 dark:bg-brand-950/40">
                <i class="fas fa-spinner fa-spin mb-2 block text-2xl text-brand-600 dark:text-brand-300"></i>
                <p class="text-sm text-slate-600 dark:text-slate-300">Procesando archivos, por favor espera…</p>
            </div>

            <x-pqrs.error message="cargar.error" />
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                    dark:border-slate-700/60">
            <button type="button" class="tw-btn-secondary" @click="cerrar()">Cancelar</button>
            <button type="submit" class="tw-btn-primary" :disabled="cargar.enviando">
                <i class="fas" :class="cargar.enviando ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                <span x-text="cargar.enviando ? 'Subiendo…' : 'Subir archivos'"></span>
            </button>
        </div>
    </form>
</x-modal>

{{-- =============================== HISTÓRICO =========================== --}}
<x-modal show="modal === 'historico'" close="cerrar()" size="max-w-[95vw]"
         title="Histórico de quejas" icon="fa-clock-rotate-left" tint="amber">
    <x-slot:subtitle>Consulta de quejas ya legalizadas</x-slot:subtitle>

    <div class="px-4 py-4 2xl:px-5 2xl:py-5">
        <form @submit.prevent="buscarHistorico()"
              class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4
                     dark:border-slate-700 dark:bg-slate-900/40 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="tw-label" for="hist_orden">Número orden</label>
                <input type="text" class="tw-input" id="hist_orden" placeholder="Ej: 12345"
                       x-model="historico.orden">
            </div>
            <div>
                <label class="tw-label" for="hist_contrato">Contrato</label>
                <input type="text" class="tw-input" id="hist_contrato" placeholder="Ej: 98765"
                       x-model="historico.contrato">
            </div>
            <div>
                <label class="tw-label" for="hist_fecha_inicio">Fecha inicio</label>
                <input type="date" class="tw-input" id="hist_fecha_inicio" x-model="historico.fechaInicio">
            </div>
            <div>
                <label class="tw-label" for="hist_fecha_fin">Fecha fin</label>
                <input type="date" class="tw-input" id="hist_fecha_fin" x-model="historico.fechaFin">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="tw-btn-primary w-full" :disabled="historico.buscando">
                    <i class="fas" :class="historico.buscando ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                    Buscar
                </button>
            </div>
        </form>

        <div class="mt-4 flex items-center justify-between gap-3">
            <p class="text-xs text-slate-500 dark:text-slate-400" x-show="historico.total > 0">
                <span class="font-semibold" x-text="historico.total"></span> registros encontrados
            </p>
            <button type="button" class="tw-btn-secondary tw-btn-sm ml-auto"
                    @click="exportarHistorico()"
                    :disabled="historico.exportando || historico.total === 0">
                <i class="fas" :class="historico.exportando ? 'fa-spinner fa-spin' : 'fa-file-excel'"></i>
                <span x-text="historico.exportando ? 'Generando…' : 'Exportar resultados'"></span>
            </button>
        </div>

        <x-pqrs.error message="historico.error" class="mt-4" />

        <div x-show="historico.buscando" x-cloak class="py-10 text-center text-slate-500 dark:text-slate-400">
            <i class="fas fa-spinner fa-spin mb-2 block text-2xl"></i>
            <span class="text-sm">Consultando…</span>
        </div>

        <div x-show="historico.vacio" x-cloak
             class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-6 text-center
                    dark:border-sky-800 dark:bg-sky-950/40">
            <i class="fas fa-circle-info mb-2 block text-2xl text-sky-500"></i>
            <span class="text-sm text-sky-800 dark:text-sky-200">
                No se encontraron registros gestionados con los criterios ingresados.
            </span>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700"
             x-show="historico.total > 0" x-cloak>
            <div id="tabla_historico" class="ht-theme-main ht-compacta" style="position: relative; width: 100%;"></div>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
    </x-slot:footer>
</x-modal>

{{-- ============================ EXPORTAR A GDW ========================== --}}
<x-modal show="modal === 'gdw'" close="cerrar()" size="max-w-lg"
         title="Exportar a GDW" icon="fa-file-export" tint="emerald">
    <x-slot:subtitle>Genera los archivos de punto de interés y tareas</x-slot:subtitle>

    <form @submit.prevent="enviarExportarGDW()">
        <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-rose-200 bg-rose-50/70 p-4
                          dark:border-rose-900/50 dark:bg-rose-950/30">
                <input type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600
                                              focus:ring-brand-500"
                       x-model="gdw.pendientes">
                <span class="text-sm font-semibold text-rose-800 dark:text-rose-200">
                    Exportar todas las pendientes (sin recepción)
                    <span class="mt-0.5 block text-xs font-normal text-rose-700/80 dark:text-rose-300/80">
                        Al marcarlo se ignora la fecha de asignación.
                    </span>
                </span>
            </label>

            <div>
                <label class="tw-label" for="fecha_exportacion">
                    <i class="far fa-calendar text-slate-400"></i> Fecha de asignación
                </label>
                <input type="date" class="tw-input" id="fecha_exportacion"
                       x-model="gdw.fecha" :disabled="gdw.pendientes" :required="!gdw.pendientes">
            </div>

            <div x-show="gdw.enviando" x-cloak
                 class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-5 text-center
                        dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <i class="fas fa-spinner fa-spin mb-2 block text-2xl text-emerald-600 dark:text-emerald-300"></i>
                <p class="text-sm text-slate-600 dark:text-slate-300">Buscando y exportando…</p>
            </div>

            <x-pqrs.error message="gdw.error" />
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                    dark:border-slate-700/60">
            <button type="button" class="tw-btn-secondary" @click="cerrar()">Cancelar</button>
            <button type="submit" class="tw-btn-primary" :disabled="gdw.enviando">
                <i class="fas" :class="gdw.enviando ? 'fa-spinner fa-spin' : 'fa-download'"></i>
                Buscar y exportar
            </button>
        </div>
    </form>
</x-modal>

{{-- ======================== EXPORTAR POR SUPERVISOR ===================== --}}
<x-modal show="modal === 'supervisor'" close="cerrar()" size="max-w-lg"
         title="Exportar por supervisor" icon="fa-user-shield" tint="violet">
    <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
        <div>
            <label class="tw-label" for="selectSupervisor">Seleccione el supervisor</label>
            <select class="tw-select" id="selectSupervisor" x-model="supervisor.seleccionado"
                    :disabled="supervisor.cargando">
                <option value="" x-text="supervisor.cargando ? 'Cargando supervisores…' : '-- Seleccione un supervisor --'"></option>
                <template x-for="s in supervisor.lista" :key="s">
                    <option :value="s" x-text="s"></option>
                </template>
            </select>
        </div>

        <x-pqrs.error message="supervisor.error" />
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cancelar</button>
        <button type="button" class="tw-btn-primary" @click="exportarSupervisor()"
                :disabled="supervisor.exportando || !supervisor.seleccionado">
            <i class="fas" :class="supervisor.exportando ? 'fa-spinner fa-spin' : 'fa-download'"></i>
            <span x-text="supervisor.exportando ? 'Procesando…' : 'Generar Excel'"></span>
        </button>
    </x-slot:footer>
</x-modal>

{{-- ============================== VER MÁS ============================== --}}
<x-modal show="modal === 'verMas'" close="cerrar()" size="max-w-2xl"
         title="Información completa" icon="fa-circle-info" tint="blue">
    <div class="px-4 py-4 2xl:px-5 2xl:py-5">
        <p class="whitespace-pre-wrap break-words rounded-xl border border-slate-200 bg-slate-50/60 p-4
                  text-sm leading-relaxed text-slate-700
                  dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200"
           x-text="verMas"></p>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
    </x-slot:footer>
</x-modal>
