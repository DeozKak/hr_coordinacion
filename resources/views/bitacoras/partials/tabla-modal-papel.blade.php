{{-- Inspección en papel. Conserva las reglas del formulario original:
     acta con prefijo "P", contrato con prefijo ":", los grupos de categoría
     y recintos se ocultan para líneas matriz, y la causal sólo aparece si el
     resultado no es CERTIFICADA. --}}
<x-modal show="modal === 'papel'" close="modal = null" size="max-w-2xl"
         icon="fa-file-circle-plus" tint="emerald" title="Agregar Inspección en Papel">
    <x-slot:subtitle>Se añade a la bitácora del inspector seleccionado</x-slot:subtitle>

    <div class="grid gap-4 p-4 2xl:p-5 sm:grid-cols-2">

        <div class="sm:col-span-2">
            <label for="p-inspector" class="tw-label">Inspector</label>
            <select id="p-inspector" class="tw-select" x-model="papel.cedula" :disabled="papelInspectorFijo"
                    :class="errores.cedula && 'border-red-500'">
                <option value="">Seleccione Inspector</option>
                <template x-for="i in inspectores" :key="i.cedula">
                    <option :value="i.cedula" x-text="i.nombre"></option>
                </template>
            </select>
        </div>

        {{-- Municipio: búsqueda remota contra municipios.json --}}
        <div class="sm:col-span-2" x-data="{ abierto: false }" @click.outside="abierto = false">
            <label for="p-municipio" class="tw-label">Municipio</label>
            <div class="relative">
                <input id="p-municipio" type="text" class="tw-input" autocomplete="off"
                       placeholder="Escribe 2 caracteres…"
                       x-model="papel.municipioTexto"
                       @focus="abierto = true"
                       @input.debounce.250ms="buscarMunicipios(); abierto = true"
                       :class="errores.municipio && 'border-red-500'">
                <i x-show="buscandoMunicipio" x-cloak
                   class="fas fa-circle-notch fa-spin absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>

                <ul x-show="abierto && municipios.length" x-cloak
                    class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200
                           bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800">
                    <template x-for="m in municipios" :key="m.id">
                        <li>
                            <button type="button"
                                    @click="papel.municipio = m.id; papel.municipioTexto = m.text; abierto = false"
                                    class="block w-full px-4 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-700"
                                    x-text="m.text"></button>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <div>
            <label for="p-fecha" class="tw-label">Fecha</label>
            <input id="p-fecha" type="date" class="tw-input" x-model="papel.fecha" :min="fechaMinima"
                   :class="errores.fecha && 'border-red-500'">
        </div>

        <div>
            <label for="p-acta" class="tw-label">N° Acta</label>
            {{-- Siempre empieza por "P", sólo dígitos después, máx. 19 --}}
            <input id="p-acta" type="text" class="tw-input" x-model="papel.acta"
                   @input="papel.acta = 'P' + papel.acta.replace(/[^0-9]/g, '').slice(0, 18)"
                   @focus="if (!papel.acta.startsWith('P')) papel.acta = 'P' + papel.acta"
                   :class="errores.acta && 'border-red-500'">
        </div>

        <div class="sm:col-span-2">
            <label for="p-tipo" class="tw-label">Tipo de trabajo</label>
            <select id="p-tipo" class="tw-select" x-model="papel.tipo"
                    :class="errores.tipo && 'border-red-500'">
                <option value="">Seleccione Tipo de Trabajo</option>
                @foreach ([
                    'FI-29 revisión periódica línea matriz',
                    'FI-31 REVISIÓN NUEVA LINEA MATRIZ',
                    'RP 10444', 'RP 12161', 'RN 12162', 'SA 12163', 'SA 12164',
                ] as $tipo)
                    <option value="{{ $tipo }}">{{ $tipo }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="p-contrato" class="tw-label">Contrato</label>
            {{-- Siempre empieza por ":", sólo dígitos después, máx. 18 --}}
            <input id="p-contrato" type="text" class="tw-input" x-model="papel.contrato"
                   @input="papel.contrato = ':' + papel.contrato.replace(/[^0-9]/g, '').slice(0, 18)"
                   @focus="if (!papel.contrato.startsWith(':')) papel.contrato = ':' + papel.contrato"
                   :class="errores.contrato && 'border-red-500'">
        </div>

        <div>
            <label for="p-resultado" class="tw-label">Resultado cierre</label>
            <select id="p-resultado" class="tw-select" x-model="papel.resultado"
                    :class="errores.resultado && 'border-red-500'">
                <option value="">Seleccione Resultado</option>
                <option value="CERTIFICADA">CERTIFICADA</option>
                <option value="INSPECCIONADA CON DEFECTO CRITICO VALLE">INSPECCIONADA CON DEFECTO CRITICO VALLE</option>
                <option value="INSPECCIONADA CON DEFECTO NO CRITICO VALLE">INSPECCIONADA CON DEFECTO NO CRITICO VALLE</option>
            </select>
        </div>

        {{-- Categoría y recintos no aplican a las líneas matriz --}}
        <div x-show="!esLineaMatriz">
            <label for="p-categoria" class="tw-label">Categoría</label>
            <select id="p-categoria" class="tw-select" x-model="papel.categoria"
                    :class="errores.categoria && 'border-red-500'">
                <option value="">Seleccione Categoría</option>
                <option value="RESIDENCIAL">RESIDENCIAL</option>
                <option value="COMERCIAL">COMERCIAL</option>
            </select>
        </div>

        <div x-show="!esLineaMatriz" class="grid grid-cols-2 gap-3">
            <div>
                <label for="p-recintos" class="tw-label">4 recintos o más</label>
                <select id="p-recintos" class="tw-select" x-model="papel.recintos"
                        @change="if (papel.recintos !== 'SI') papel.cantidadRecintos = ''">
                    <option value="NO">NO</option>
                    <option value="SI">SI</option>
                </select>
            </div>
            <div>
                <label for="p-cantidad" class="tw-label">Cantidad</label>
                <input id="p-cantidad" type="text" inputmode="numeric" class="tw-input text-center"
                       x-model="papel.cantidadRecintos" :disabled="papel.recintos !== 'SI'"
                       @input="papel.cantidadRecintos = papel.cantidadRecintos.replace(/\D/g, '').slice(0, 3)"
                       :class="errores.cantidadRecintos && 'border-red-500'">
            </div>
        </div>

        <div x-show="requiereCausal" x-cloak class="sm:col-span-2">
            <label for="p-causal" class="tw-label">Causal de rechazo</label>
            <input id="p-causal" type="text" class="tw-input" x-model="papel.causal"
                   :class="errores.causal && 'border-red-500'">
        </div>
    </div>

    <x-slot:footer>
        <button type="button" @click="modal = null" class="tw-btn-secondary">Cerrar</button>
        <button type="button" @click="agregarPapel()" :disabled="agregando" class="tw-btn-primary">
            <i class="fas" :class="agregando ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
            <span x-text="agregando ? 'Agregando…' : 'Agregar'"></span>
        </button>
    </x-slot:footer>
</x-modal>
