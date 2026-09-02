@props(['items' => []])

{{-- Leyenda del código de color de una tabla.
     Las muestras se pintan desde `paleta()` del propio componente Alpine, así
     que no hay una copia de los colores en CSS que se pueda desincronizar del
     renderizador (y el cambio de tema las actualiza solas). --}}
<div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-200/80 px-5 py-3
            dark:border-slate-700/60">
    <span class="tw-eyebrow">Código de color</span>
    @foreach ($items as [$clave, $texto])
        <span class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
            <span class="h-3.5 w-3.5 shrink-0 rounded border border-black/10"
                  :style="{ backgroundColor: paleta()['{{ $clave }}'] }"></span>
            {{ $texto }}
        </span>
    @endforeach
</div>
