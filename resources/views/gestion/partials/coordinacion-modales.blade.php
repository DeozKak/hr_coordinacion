{{-- =========================== IMPRESIÓN MASIVA ========================== --}}
<x-modal show="modal === 'impresion'" close="modal = null" size="max-w-lg"
         icon="fa-print" tint="blue" title="Impresión masiva">
    <x-slot:subtitle>Genera las planillas de una sede completa.</x-slot:subtitle>

    {{-- Envío normal del formulario, no por fetch: la respuesta es la descarga
         del archivo, igual que en la versión anterior. --}}
    <form method="post" action="{{ route('generarImpMasiva') }}" autocomplete="off"
          @submit="if (!impresionValida()) $event.preventDefault()">
        @csrf

        <div class="space-y-4 px-5 py-5">
            <div>
                <label class="tw-label" for="sedeImpMas">Sede</label>
                <select class="tw-select" name="sedeImpMas" id="sedeImpMas" x-model="impresion.sede">
                    @foreach ($sedes as $sede)
                        <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="tw-label" for="tipoOrden">Tipo de orden</label>
                    <select class="tw-select" name="tipoOrden" id="tipoOrden" x-model="impresion.tipoOrden">
                        <option value="">Ambas</option>
                        <option value="1">Masiva</option>
                        <option value="2">Externa</option>
                    </select>
                </div>

                <div>
                    <label class="tw-label" for="fechaAsigna">¿Fecha de asignación?</label>
                    <select class="tw-select" name="fechaAsigna" id="fechaAsigna" x-model="impresion.fechaAsigna">
                        <option value="si">Sí</option>
                        <option value="no">No</option>
                    </select>
                </div>
            </div>

            <div x-show="impresion.fechaAsigna === 'si'" x-cloak x-transition.opacity>
                <label class="tw-label" for="fechaImpMasiva">Fecha</label>
                <input class="tw-input" type="date" name="fechaImpMasiva" id="fechaImpMasiva"
                       x-model="impresion.fecha">
            </div>

            <div>
                <span class="tw-label">Exportar</span>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        ['campo' => 'expExcel', 'modelo' => 'excel', 'etiqueta' => 'Excel', 'icono' => 'fa-file-excel'],
                        ['campo' => 'expPdf',   'modelo' => 'pdf',   'etiqueta' => 'PDF',   'icono' => 'fa-file-pdf'],
                    ] as $exp)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3.5
                                      transition hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700/40"
                               :class="impresion.{{ $exp['modelo'] }} && 'border-brand-400 bg-brand-50/50 dark:border-brand-500 dark:bg-brand-900/20'">
                            <input type="checkbox" name="{{ $exp['campo'] }}"
                                   x-model="impresion.{{ $exp['modelo'] }}"
                                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <i class="fas {{ $exp['icono'] }} text-slate-400"></i>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                {{ $exp['etiqueta'] }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <p x-show="errorImpresion" x-cloak
               class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                      dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
               x-text="errorImpresion"></p>
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                    dark:border-slate-700/60">
            <button type="button" class="tw-btn-secondary" @click="modal = null">Cancelar</button>
            <button type="submit" class="tw-btn-primary">
                <i class="fas fa-print"></i> Generar
            </button>
        </div>
    </form>
</x-modal>
