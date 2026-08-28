@props([
    'label',                 // etiqueta encima del recuadro
    'hint' => '',            // línea pequeña con los formatos aceptados
    'ref',                   // x-ref con el que el componente lee los archivos
    'model',                 // expresión Alpine donde se guarda el texto mostrado
    'tint' => 'sky',
    'icon' => 'fa-file-arrow-up',
    'multiple' => false,
    'accept' => null,
])

{{-- Mismo patrón que el cargue del home y el de bitácoras/generar: el input real
     va oculto (sr-only) y la caja punteada es la que se ve y recibe el clic. --}}
<div>
    <span class="tw-label">{{ $label }}</span>
    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-4 py-4
                  transition hover:border-brand-400 hover:bg-brand-50/40
                  dark:border-slate-600 dark:hover:bg-slate-700/40">
        <span class="tw-chip chip-{{ $tint }}"><i class="fas {{ $icon }}"></i></span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium text-slate-700 dark:text-slate-200"
                  x-text="{{ $model }} || '{{ $multiple ? 'Seleccionar archivos…' : 'Seleccionar archivo…' }}'"></span>
            @if($hint)
                <span class="block text-xs text-slate-400">{{ $hint }}</span>
            @endif
        </span>
        <input type="file" class="sr-only" x-ref="{{ $ref }}"
               @if($multiple) multiple @endif
               @if($accept) accept="{{ $accept }}" @endif
               @change="{{ $model }} = $event.target.files.length > 1
                            ? $event.target.files.length + ' archivos seleccionados'
                            : ($event.target.files[0]?.name ?? '')">
    </label>
</div>
