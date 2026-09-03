{{-- Ventana con el contenido completo de un valor de auditoría. --}}
<x-modal show="verMas !== null" close="verMas = null" size="max-w-4xl"
         icon="fa-code" tint="slate" title="Detalle completo">
    <x-slot:subtitle><span x-text="verMas?.clave ?? ''"></span></x-slot:subtitle>

    <div class="px-4 py-4 2xl:px-5 2xl:py-5">
        <pre class="max-h-[60vh] overflow-auto whitespace-pre-wrap break-words rounded-xl border
                    border-slate-200 bg-slate-50 p-4 font-mono text-xs leading-relaxed text-slate-700
                    dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300"
             x-text="verMas?.texto ?? ''"></pre>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="verMas = null">Cerrar</button>
    </x-slot:footer>
</x-modal>
