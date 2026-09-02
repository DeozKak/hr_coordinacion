{{-- Diálogos y toasts. El motor está en resources/js/alerts.js y expone
     window.Swal con la misma API que SweetAlert2, así que las 259 llamadas
     existentes siguen funcionando sin tocarlas.
     El markup vive aquí (y no en el JS) para que Tailwind vea las clases.

     Dos detalles: se usa x-show y no x-if porque x-transition sólo funciona
     con el primero; y el z-index va por encima de 9999, que es lo más alto
     que usa Handsontable (si no, la grilla se dibuja sobre el velo). --}}
@php
    $tintes = [
        'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300',
        'rose'    => 'bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-300',
        'amber'   => 'bg-amber-100 text-amber-600 dark:bg-amber-900/50 dark:text-amber-300',
        'sky'     => 'bg-sky-100 text-sky-600 dark:bg-sky-900/50 dark:text-sky-300',
        'violet'  => 'bg-violet-100 text-violet-600 dark:bg-violet-900/50 dark:text-violet-300',
    ];
    $posiciones = [
        'top'          => 'top-4 left-1/2 -translate-x-1/2 items-center',
        'top-start'    => 'top-4 left-4 items-start',
        'top-end'      => 'top-4 right-4 items-end',
        'center'       => 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 items-center',
        'bottom'       => 'bottom-4 left-1/2 -translate-x-1/2 items-center',
        'bottom-start' => 'bottom-4 left-4 items-start',
        'bottom-end'   => 'bottom-4 right-4 items-end',
    ];
@endphp

<div x-data data-capa-alertas>
    {{-- ==================== DIÁLOGO ==================== --}}
    {{-- La raíz también necesita transición: sin ella se ocultaba de golpe al
         cerrar y las transiciones del velo y del cuadro no se llegaban a ver. --}}
    <div x-show="$store.alertas.visible" x-cloak
         x-transition:enter="transition duration-200 ease-out"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition duration-150 ease-in"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[10010] overflow-y-auto" role="dialog" aria-modal="true"
         @keydown.escape.window="$store.alertas.dialogo.cerrarEscape && Swal._cancelar()">

        {{-- Velo --}}
        <div x-show="$store.alertas.visible"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
             @click="$store.alertas.dialogo.cerrarFuera && Swal._cancelar()"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div id="alerta-popup"
                 x-show="$store.alertas.visible"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 x-trap="$store.alertas.visible"
                 x-data="{ bloqueado: false }"
                 x-effect="if ($store.alertas.visible && !bloqueado) { bloqueado = true; Alpine.store('scroll').bloquear(); }
                           else if (!$store.alertas.visible && bloqueado) { bloqueado = false; Alpine.store('scroll').liberar(); }"
                 :style="$store.alertas.dialogo.ancho ? `max-width:${$store.alertas.dialogo.ancho}` : ''"
                 :class="$store.alertas.dialogo.claseExtra"
                 class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white
                        p-6 text-center shadow-2xl dark:border-slate-700 dark:bg-slate-800">

                {{-- Icono --}}
                <div x-show="$store.alertas.dialogo.icono"
                     x-transition:enter="transition ease-out duration-300 delay-100"
                     x-transition:enter-start="opacity-0 scale-50"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full"
                     :class="{
                        @foreach ($tintes as $clave => $clases)
                            '{{ $clases }}': $store.alertas.dialogo.icono?.tinte === '{{ $clave }}',
                        @endforeach
                     }">
                    <i class="fas text-2xl" :class="$store.alertas.dialogo.icono?.fa"></i>
                </div>

                <h2 x-show="$store.alertas.dialogo.titulo"
                    class="text-xl font-bold leading-snug tracking-tight text-slate-900 dark:text-white"
                    x-text="$store.alertas.dialogo.titulo"></h2>

                <p x-show="$store.alertas.dialogo.texto"
                   class="mt-2 text-[15px] leading-relaxed text-slate-500 dark:text-slate-400"
                   x-text="$store.alertas.dialogo.texto"></p>

                <div x-show="$store.alertas.dialogo.html"
                     class="mt-3 text-[15px] leading-relaxed text-slate-500 dark:text-slate-400"
                     x-html="$store.alertas.dialogo.html"></div>

                {{-- Campo de entrada --}}
                <div x-show="$store.alertas.dialogo.input" class="mt-5 text-left">
                    <template x-if="$store.alertas.dialogo.input === 'textarea'">
                        <textarea data-alerta-foco rows="3" class="tw-input"
                                  x-model="$store.alertas.dialogo.valor"
                                  :placeholder="$store.alertas.dialogo.inputPlaceholder"></textarea>
                    </template>

                    <template x-if="$store.alertas.dialogo.input === 'select'">
                        <select data-alerta-foco class="tw-select" x-model="$store.alertas.dialogo.valor">
                            <template x-for="(etiqueta, valor) in ($store.alertas.dialogo.inputOptions ?? {})" :key="valor">
                                <option :value="valor" x-text="etiqueta"></option>
                            </template>
                        </select>
                    </template>

                    <template x-if="$store.alertas.dialogo.input
                                    && !['textarea','select'].includes($store.alertas.dialogo.input)">
                        <input data-alerta-foco class="tw-input"
                               :type="$store.alertas.dialogo.input"
                               x-model="$store.alertas.dialogo.valor"
                               :placeholder="$store.alertas.dialogo.inputPlaceholder">
                    </template>
                </div>

                {{-- Mensaje de validación (Swal.showValidationMessage) --}}
                <p x-show="$store.alertas.dialogo.validacion" x-cloak
                   x-transition:enter="transition ease-out duration-150"
                   x-transition:enter-start="opacity-0 -translate-y-1"
                   x-transition:enter-end="opacity-100 translate-y-0"
                   class="mt-3 flex items-center gap-2 rounded-xl bg-red-50 px-3 py-2 text-left text-sm
                          text-red-700 dark:bg-red-950/50 dark:text-red-300"
                   role="alert">
                    <i class="fas fa-circle-exclamation shrink-0"></i>
                    <span x-text="$store.alertas.dialogo.validacion"></span>
                </p>

                {{-- Botones --}}
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    <button type="button" x-show="$store.alertas.dialogo.mostrarConfirmar"
                            @click="Swal._confirmar()" :disabled="$store.alertas.dialogo.cargando"
                            :style="$store.alertas.dialogo.colorConfirmar
                                ? `background-color:${$store.alertas.dialogo.colorConfirmar}` : ''"
                            class="tw-btn-primary min-w-[6rem]">
                        <i class="fas fa-spinner fa-spin" x-show="$store.alertas.dialogo.cargando" x-cloak></i>
                        <span x-text="$store.alertas.dialogo.textoConfirmar"></span>
                    </button>

                    <button type="button" x-show="$store.alertas.dialogo.mostrarDenegar"
                            @click="Swal._denegar()"
                            class="tw-btn-danger min-w-[6rem]"
                            x-text="$store.alertas.dialogo.textoDenegar"></button>

                    <button type="button" x-show="$store.alertas.dialogo.mostrarCancelar"
                            @click="Swal._cancelar()"
                            :style="$store.alertas.dialogo.colorCancelar
                                ? `background-color:${$store.alertas.dialogo.colorCancelar};color:#fff;border-color:transparent` : ''"
                            class="tw-btn-secondary min-w-[6rem]"
                            x-text="$store.alertas.dialogo.textoCancelar"></button>
                </div>

                <div x-show="$store.alertas.dialogo.footer" x-cloak
                     class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-500 dark:border-slate-700"
                     x-html="$store.alertas.dialogo.footer"></div>
            </div>
        </div>
    </div>

    {{-- ==================== TOASTS ==================== --}}
    @foreach ($posiciones as $clave => $clases)
        <div class="pointer-events-none fixed z-[10020] flex flex-col gap-2 {{ $clases }}">
            <template x-for="t in $store.alertas.toasts.filter(t => t.posicion === '{{ $clave }}')" :key="t.id">
                <div x-show="t.visible"
                     x-transition:enter="transition ease-out duration-250"
                     x-transition:enter-start="opacity-0 scale-90 -translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-90 translate-x-4"
                     class="pointer-events-auto flex w-80 max-w-[calc(100vw-2rem)] items-start gap-3 rounded-2xl
                            border border-slate-200 bg-white p-3.5 shadow-lg
                            dark:border-slate-700 dark:bg-slate-800">
                    <template x-if="t.icono">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                              :class="{
                                @foreach ($tintes as $c => $cls)
                                    '{{ $cls }}': t.icono.tinte === '{{ $c }}',
                                @endforeach
                              }">
                            <i class="fas text-sm" :class="t.icono.fa"></i>
                        </span>
                    </template>

                    <div class="min-w-0 flex-1 pt-0.5">
                        <p x-show="t.titulo" class="text-sm font-semibold leading-snug text-slate-900 dark:text-white"
                           x-text="t.titulo"></p>
                        <p x-show="t.texto" class="text-sm text-slate-500 dark:text-slate-400" x-text="t.texto"></p>
                    </div>

                    <button type="button" @click="$store.alertas.ocultarToast(t.id)"
                            class="shrink-0 rounded-lg p-1 text-slate-300 transition hover:text-slate-500"
                            aria-label="Cerrar aviso">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>
            </template>
        </div>
    @endforeach
</div>
