{{-- Celda de valores de auditoría. Muestra los primeros campos y deja el resto
     tras un botón: hay registros con más de veinte y la fila se volvía una
     columna de texto de media pantalla de alto.
     `$expr` es la expresión Alpine con el objeto de valores, y `$titulo` el
     encabezado que verá la ventana con el contenido completo. --}}
<template x-if="!{{ $expr }}">
    <span class="text-slate-400">N/A</span>
</template>

<template x-if="{{ $expr }}">
    <div class="min-w-0 max-w-md space-y-0.5">
        <template x-for="par in paresVisibles({{ $expr }})" :key="par.clave">
            {{-- min-w-0: un elemento flex no baja de su ancho de contenido por
                 defecto, así que sin esto el `truncate` del valor no llega a
                 activarse y el texto ensancha la columna en vez de recortarse. --}}
            <div class="flex min-w-0 items-baseline gap-1.5">
                <span class="shrink-0 text-xs font-semibold text-slate-600 dark:text-slate-300"
                      x-text="par.clave + ':'"></span>
                <span class="truncate text-xs text-slate-500 dark:text-slate-400"
                      :title="aTexto(par.valor)" x-text="fragmento(par.valor, 40)"></span>
            </div>
        </template>

        <button type="button"
                class="mt-1 text-xs font-medium text-brand-600 hover:underline dark:text-brand-300"
                @click="abrirJson(@js($titulo ?? 'Valores'), {{ $expr }})">
            <span x-show="paresOcultos({{ $expr }}) > 0"
                  x-text="`+${paresOcultos({{ $expr }})} campos más`"></span>
            <span x-show="paresOcultos({{ $expr }}) === 0">ver todo</span>
        </button>
    </div>
</template>
