{{-- Barra de paginación. El componente expone paginaActual, totalPaginas,
     filtrados y porPagina. --}}
<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 px-5 py-3
            dark:border-slate-700/60"
     x-show="filtrados.length > porPagina" x-cloak>
    <p class="text-xs text-slate-500 dark:text-slate-400">
        Página <span class="font-semibold" x-text="paginaActual"></span>
        de <span class="font-semibold" x-text="totalPaginas"></span>
        · <span x-text="filtrados.length"></span> registros
    </p>

    <div class="flex items-center gap-2">
        <select class="tw-select w-auto py-1.5 text-xs" x-model.number="porPagina" @change="pagina = 1">
            <template x-for="n in [25, 50, 100]" :key="n">
                <option :value="n" x-text="`${n} / página`"></option>
            </template>
        </select>
        <button type="button" class="tw-btn-secondary tw-btn-sm"
                :disabled="paginaActual <= 1" @click="pagina = paginaActual - 1">
            <i class="fas fa-chevron-left"></i> Anterior
        </button>
        <button type="button" class="tw-btn-secondary tw-btn-sm"
                :disabled="paginaActual >= totalPaginas" @click="pagina = paginaActual + 1">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>
