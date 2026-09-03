{{-- =====================================================================
     Modales de Control de Stickers.
     Se renderizan dentro del x-data="controlStickers(...)" de index.blade.php,
     por eso acceden directamente a su estado (modal, agregar, asignar, …).
     ===================================================================== --}}

{{-- ============================ AGREGAR A INVENTARIO ==================== --}}
@can('control_stickers')
    <x-modal show="modal === 'agregar'" close="cerrar()" size="max-w-lg"
             title="Agregar stickers a inventario" icon="fa-plus" tint="blue">
        <x-slot:subtitle>Suma unidades o registra un rango de seriales</x-slot:subtitle>

        <form @submit.prevent="enviarAgregar()" id="formAgregarSticker">
            <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
                <div>
                    <label class="tw-label" for="tipoSticker">Tipo de sticker</label>
                    <select class="tw-select" id="tipoSticker" x-model="agregar.tipo">
                        <option value="">-- Seleccione un tipo --</option>
                        @foreach($tipos as $t)
                            <option value="{{ $t['id'] }}">{{ $t['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Cantidad: stickers cuantitativos --}}
                <div x-show="!agregarEsActa" x-cloak>
                    <label class="tw-label" for="cantidad">Cantidad a agregar</label>
                    <input type="text" inputmode="numeric" class="tw-input" id="cantidad"
                           placeholder="Ej: 50" x-model="agregar.cantidad"
                           @input="agregar.cantidad = soloDigitos(agregar.cantidad, 10000)">
                    <p class="tw-hint"><i class="fas fa-circle-info"></i> Se suma al saldo actual del tipo seleccionado.</p>
                </div>

                {{-- Seriales: sólo ACTAS --}}
                <div class="grid gap-4 sm:grid-cols-2" x-show="agregarEsActa" x-cloak>
                    <div>
                        <label class="tw-label" for="serial_inicio">Serial inicial</label>
                        <input type="text" inputmode="numeric" class="tw-input" id="serial_inicio"
                               placeholder="Ej: 1001" x-model="agregar.serialInicio"
                               @input="agregar.serialInicio = soloDigitos(agregar.serialInicio)">
                    </div>
                    <div>
                        <label class="tw-label" for="serial_fin">Serial final</label>
                        <input type="text" inputmode="numeric" class="tw-input" id="serial_fin"
                               placeholder="Ej: 1100" x-model="agregar.serialFin"
                               @input="agregar.serialFin = soloDigitos(agregar.serialFin)">
                    </div>
                    <p class="tw-hint sm:col-span-2" x-show="agregar.serialInicio && agregar.serialFin">
                        <i class="fas fa-hashtag"></i>
                        Se registrarán <strong x-text="totalSerialesAgregar"></strong> seriales.
                    </p>
                </div>

                <x-stickers.error message="agregar.error" />
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                        dark:border-slate-700/60">
                <button type="button" class="tw-btn-secondary" @click="cerrar()">Cancelar</button>
                <button type="submit" class="tw-btn-primary" :disabled="agregar.enviando">
                    <i class="fas" :class="agregar.enviando ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
                    <span x-text="agregar.enviando ? 'Procesando…' : 'Agregar'"></span>
                </button>
            </div>
        </form>
    </x-modal>

    {{-- ============================== ASIGNAR =========================== --}}
    <x-modal show="modal === 'asignar'" close="cerrar()" size="max-w-3xl"
             title="Asignar stickers" icon="fa-user-plus" tint="emerald">
        <x-slot:subtitle>
            <span x-text="asignar.nombre"></span>
        </x-slot:subtitle>

        <form @submit.prevent="enviarAsignar()" id="formAsignarSticker">
            <div class="px-4 py-4 2xl:px-5 2xl:py-5">
                <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                    <table class="tw-table">
                        <thead>
                        <tr>
                            <th>Tipo de sticker</th>
                            <th>Entrada (cantidad o rango)</th>
                            <th class="text-center">Disponible</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template x-for="t in tipos" :key="t.id">
                            <tr>
                                <td>
                                    <span class="inline-flex items-center gap-2 text-sm font-semibold
                                                 text-slate-700 dark:text-slate-200">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                              :style="`background-color: ${t.color}`"></span>
                                        <span x-text="t.nombre"></span>
                                    </span>
                                </td>
                                <td>
                                    {{-- ACTAS: rango de seriales --}}
                                    <template x-if="t.esActa">
                                        <div class="flex items-center gap-2">
                                            <input type="text" inputmode="numeric" class="tw-input py-1.5 text-sm"
                                                   placeholder="Serial inicial" x-model="asignar.serialInicio"
                                                   @input="asignar.serialInicio = soloDigitos(asignar.serialInicio)">
                                            <span class="text-slate-400">–</span>
                                            <input type="text" inputmode="numeric" class="tw-input py-1.5 text-sm"
                                                   placeholder="Serial final" x-model="asignar.serialFin"
                                                   @input="asignar.serialFin = soloDigitos(asignar.serialFin)">
                                        </div>
                                    </template>
                                    {{-- Resto: cantidad, topada al inventario disponible --}}
                                    <template x-if="!t.esActa">
                                        <input type="text" inputmode="numeric" class="tw-input py-1.5 text-sm"
                                               placeholder="Cantidad"
                                               :disabled="(asignar.disponible[t.id] ?? 0) === 0"
                                               :value="asignar.cantidades[t.id] ?? ''"
                                               @input="asignar.cantidades[t.id] =
                                                    soloDigitos($event.target.value, asignar.disponible[t.id] ?? 0);
                                                    $event.target.value = asignar.cantidades[t.id]">
                                    </template>
                                </td>
                                <td class="text-center">
                                    <span class="tw-pill"
                                          :class="saldoAsignar(t) > 0 ? 'pill-sky' : 'pill-slate'"
                                          x-text="saldoAsignar(t)"></span>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>

                <x-stickers.error message="asignar.error" class="mt-4" />
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                        dark:border-slate-700/60">
                <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
                <button type="submit" class="tw-btn-primary" :disabled="asignar.enviando">
                    <i class="fas" :class="asignar.enviando ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="asignar.enviando ? 'Asignando…' : 'Asignar'"></span>
                </button>
            </div>
        </form>
    </x-modal>

    {{-- ============================= DESASIGNAR ========================= --}}
    <x-modal show="modal === 'desasignar'" close="cerrar()" size="max-w-3xl"
             title="Desasignar stickers" icon="fa-user-minus" tint="rose">
        <x-slot:subtitle>
            <span x-text="desasignar.nombre"></span>
        </x-slot:subtitle>

        <form @submit.prevent="enviarDesasignar()" id="formDesasignarSticker">
            <div class="px-4 py-4 2xl:px-5 2xl:py-5">
                <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                    <table class="tw-table">
                        <thead>
                        <tr>
                            <th>Tipo de sticker</th>
                            <th>Entrada (cantidad o rango)</th>
                            <th class="text-center">Asignado</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template x-for="t in tipos" :key="t.id">
                            <tr>
                                <td>
                                    <span class="inline-flex items-center gap-2 text-sm font-semibold
                                                 text-slate-700 dark:text-slate-200">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                              :style="`background-color: ${t.color}`"></span>
                                        <span x-text="t.nombre"></span>
                                    </span>
                                </td>
                                <td>
                                    <template x-if="t.esActa">
                                        <div class="flex items-center gap-2">
                                            <input type="text" inputmode="numeric" class="tw-input py-1.5 text-sm"
                                                   placeholder="Serial inicial" x-model="desasignar.serialInicio"
                                                   @input="desasignar.serialInicio = soloDigitos(desasignar.serialInicio)">
                                            <span class="text-slate-400">–</span>
                                            <input type="text" inputmode="numeric" class="tw-input py-1.5 text-sm"
                                                   placeholder="Serial final" x-model="desasignar.serialFin"
                                                   @input="desasignar.serialFin = soloDigitos(desasignar.serialFin)">
                                        </div>
                                    </template>
                                    <template x-if="!t.esActa">
                                        <input type="text" inputmode="numeric" class="tw-input py-1.5 text-sm"
                                               placeholder="Cantidad a devolver"
                                               :disabled="desasignar.cargando || (desasignar.asignado[t.id] ?? 0) === 0"
                                               :value="desasignar.cantidades[t.id] ?? ''"
                                               @input="desasignar.cantidades[t.id] =
                                                    soloDigitos($event.target.value, desasignar.asignado[t.id] ?? 0);
                                                    $event.target.value = desasignar.cantidades[t.id]">
                                    </template>
                                </td>
                                <td class="text-center">
                                    <template x-if="desasignar.cargando">
                                        <i class="fas fa-spinner fa-spin text-slate-400"></i>
                                    </template>
                                    <template x-if="!desasignar.cargando">
                                        <span class="tw-pill"
                                              :class="(desasignar.asignado[t.id] ?? 0) > 0 ? 'pill-emerald' : 'pill-slate'"
                                              x-text="desasignar.asignado[t.id] ?? 0"></span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>

                <x-stickers.error message="desasignar.error" class="mt-4" />
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                        dark:border-slate-700/60">
                <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
                <button type="submit" class="tw-btn-danger" :disabled="desasignar.enviando || desasignar.cargando">
                    <i class="fas" :class="desasignar.enviando ? 'fa-spinner fa-spin' : 'fa-minus'"></i>
                    <span x-text="desasignar.enviando ? 'Desasignando…' : 'Desasignar'"></span>
                </button>
            </div>
        </form>
    </x-modal>
@endcan

{{-- ===================== SERIALES DE ACTAS (consulta) =================== --}}
<x-modal show="modal === 'seriales'" close="cerrar()" size="max-w-lg"
         icon="fa-list-ol" tint="violet">
    <x-slot:titleSlot><span x-text="seriales.titulo"></span></x-slot:titleSlot>
    <x-slot:subtitle><span x-text="seriales.subtitulo"></span></x-slot:subtitle>

    <div class="px-4 py-4 2xl:px-5 2xl:py-5">
        <div x-show="seriales.cargando" class="py-8 text-center text-slate-500 dark:text-slate-400">
            <i class="fas fa-spinner fa-spin mb-2 block text-2xl"></i>
            <span class="text-sm">Cargando…</span>
        </div>

        <x-stickers.error message="seriales.error" />

        <template x-if="!seriales.cargando && !seriales.error">
            <div>
                <template x-if="seriales.rangos.length === 0">
                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-6 text-center
                                dark:border-sky-800 dark:bg-sky-950/40">
                        <i class="fas fa-circle-info mb-2 block text-2xl text-sky-500"></i>
                        <span class="text-sm text-sky-800 dark:text-sky-200" x-text="seriales.vacio"></span>
                    </div>
                </template>

                <div x-show="seriales.rangos.length > 0" class="max-h-[25rem] space-y-2 overflow-y-auto pr-1">
                    <template x-for="(rango, i) in seriales.rangos" :key="i">
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5
                                    dark:border-slate-700 dark:bg-slate-800/60">
                            <span class="tw-chip chip-violet h-8 w-8 text-xs" x-text="i + 1"></span>
                            <span class="font-mono text-sm font-semibold text-slate-700 dark:text-slate-200"
                                  x-text="rango"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrar()">Cerrar</button>
    </x-slot:footer>
</x-modal>
