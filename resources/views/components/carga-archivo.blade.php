@props([
    'titulo',
    'action',
    'campo' => 'archivo',
    'icon' => 'fa-file-excel',
    'tint' => 'emerald',
    'accept' => '.xls',
    'hint' => 'El archivo debe ser de tipo .xls.',
])

{{-- Tarjeta de carga de archivo.

     El <input type="file"> nativo no se puede tematizar, así que se esconde y
     el botón visible lo dispara. Se muestra el nombre de lo elegido y no se
     deja enviar sin archivo: el servidor validaba el tipo, pero la única señal
     de que había algo seleccionado era el texto gris del control nativo. --}}

<form method="POST" action="{{ $action }}" enctype="multipart/form-data"
      class="tw-card flex flex-col"
      x-data="{ archivo: '', enviando: false }"
      @submit="enviando = true">
    @csrf

    <div class="tw-card-header">
        <div class="flex items-center gap-3">
            <span class="tw-chip chip-{{ $tint }}"><i class="fas {{ $icon }}"></i></span>
            <div>
                <h2 class="tw-card-title">{{ $titulo }}</h2>
                <p class="tw-card-subtitle">{{ $hint }}</p>
            </div>
        </div>
    </div>

    <div class="flex-1 p-5">
        <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2
                      border-dashed border-slate-300 px-5 py-8 text-center transition
                      hover:border-brand-400 hover:bg-slate-50
                      dark:border-slate-600 dark:hover:border-brand-500 dark:hover:bg-slate-700/40"
               :class="archivo && 'border-brand-400 bg-brand-50/50 dark:border-brand-500 dark:bg-brand-900/20'">

            <input type="file" name="{{ $campo }}" accept="{{ $accept }}" required class="sr-only"
                   @change="archivo = $event.target.files[0]?.name ?? ''">

            <i class="fas fa-cloud-arrow-up text-2xl text-slate-400"></i>

            <span class="text-sm font-medium text-slate-700 dark:text-slate-200"
                  x-text="archivo || 'Elige un archivo o suéltalo aquí'"></span>

            <span class="text-xs text-slate-500" x-show="!archivo">{{ $accept }}</span>
            <span class="text-xs text-brand-600 dark:text-brand-300" x-show="archivo" x-cloak>
                Listo para subir
            </span>
        </label>
    </div>

    <div class="flex justify-end border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/60">
        <button type="submit" class="tw-btn-primary" :disabled="!archivo || enviando">
            <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
            <span x-text="enviando ? 'Subiendo…' : 'Subir archivo'"></span>
        </button>
    </div>
</form>
