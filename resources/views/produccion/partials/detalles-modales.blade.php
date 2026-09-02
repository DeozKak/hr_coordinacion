{{-- ===================== MODAL: INSPECCIONES DEL DÍA ===================== --}}
<x-modal show="modal === 'dia'" close="cerrarDia()" size="max-w-[95vw]"
         icon="fa-calendar-day" tint="blue">
    <x-slot:titleSlot><span x-text="tituloDia"></span></x-slot:titleSlot>
    <x-slot:subtitle>
        <span x-show="cantidadDobles" x-text="cantidadDobles"></span>
        <span x-show="!cantidadDobles">Inspecciones registradas en el día</span>
    </x-slot:subtitle>

    <div class="px-5 py-5">
        <div x-show="sinDatosDia" x-cloak
             class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900
                    dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            No hay inspecciones registradas para este día.
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
            <div id="contratos_dia" class="ht-theme-main ht-compacta"></div>
        </div>
    </div>

    <x-slot:footer>
        <div class="flex w-full flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap gap-2">
                {{-- Estos botones sustituyen a los que el JS anterior inyectaba en el
                     DOM leyendo el color de fondo de la celda. Ahora salen del estado. --}}
                <template x-for="b in botonesDobles" :key="b.id">
                    <button type="button" class="tw-btn-secondary tw-btn-sm" @click="accionDobles(b)">
                        <i class="fas" :class="b.icono"></i> <span x-text="b.texto"></span>
                    </button>
                </template>

                <button type="button" class="tw-btn-primary tw-btn-sm" @click="abrirAgregar()"
                        :disabled="!puedeAgregar">
                    <i class="fas fa-plus"></i> Agregar inspección
                </button>
            </div>

            <button type="button" class="tw-btn-secondary" @click="cerrarDia()">Cerrar</button>
        </div>
    </x-slot:footer>
</x-modal>

{{-- ==================== MODAL: CONTAR DOBLES (SÁBADO) ==================== --}}
<x-modal show="modal === 'contarSabado'" close="modal = 'dia'" size="max-w-md"
         title="Contar dobles" icon="fa-calculator" tint="amber">
    <x-slot:subtitle>Sábado con inspecciones dobles</x-slot:subtitle>

    <div class="space-y-4 px-5 py-5">
        <div>
            <label class="tw-label" for="contarSabado">
                Inspecciones a contar
                <span class="font-normal text-slate-400">
                    máx (<span x-text="seleccion.cantInspecciones"></span>)
                </span>
            </label>
            <input type="text" inputmode="numeric" class="tw-input" id="contarSabado"
                   x-model="contarSabado"
                   @input="contarSabado = contarSabado.replace(/[^0-9]/g, '')">
        </div>

        <x-pqrs.error message="errorSabado" />
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="modal = 'dia'">Cancelar</button>
        <button type="button" class="tw-btn-primary" @click="guardarContarSabado()"
                :disabled="guardandoSabado">
            <i class="fas" :class="guardandoSabado ? 'fa-spinner fa-spin' : 'fa-check'"></i> Guardar
        </button>
    </x-slot:footer>
</x-modal>

{{-- ==================== MODAL: AGREGAR INSPECCIÓN ======================== --}}
<x-modal show="modal === 'agregar'" close="modal = 'dia'" size="max-w-4xl"
         title="Agregar inspección" icon="fa-plus" tint="emerald">
    <x-slot:subtitle><span x-text="form.nombreInspector"></span></x-slot:subtitle>

    <div class="px-5 py-5">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="tw-label">Inspector</label>
                <input type="text" class="tw-input" x-model="form.nombreInspector" disabled>
            </div>

            {{-- Municipio: combobox contra municipios.json (sustituye a select2). --}}
            <div class="relative" @click.outside="municipios.abierto = false">
                <label class="tw-label" for="municipio-select">Municipio</label>
                <input type="text" class="tw-input" id="municipio-select" autocomplete="off"
                       placeholder="Escribe al menos 2 letras…"
                       x-model="municipios.busqueda"
                       @input.debounce.250ms="buscarMunicipios()"
                       @focus="municipios.abierto = municipios.lista.length > 0"
                       :class="errores.municipio && 'border-red-400'">
                <div x-show="municipios.abierto" x-cloak
                     class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200
                            bg-white p-1 shadow-xl dark:border-slate-700 dark:bg-slate-800">
                    <template x-for="m in municipios.lista" :key="m">
                        <button type="button"
                                class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50
                                       dark:hover:bg-slate-700/50"
                                @click="form.municipio = m; municipios.busqueda = m; municipios.abierto = false"
                                x-text="m"></button>
                    </template>
                    <p x-show="municipios.lista.length === 0"
                       class="px-3 py-3 text-center text-sm text-slate-400">Sin coincidencias.</p>
                </div>
            </div>

            <div>
                <label class="tw-label" for="fecha">Fecha</label>
                <input type="date" class="tw-input" id="fecha" x-model="form.fecha" :min="form.fechaMinima" disabled>
            </div>

            <div>
                <label class="tw-label" for="acta">N° acta</label>
                {{-- Prefijo "P" obligatorio, solo dígitos después, máximo 19. --}}
                <input type="text" class="tw-input" id="acta" x-model="form.acta"
                       @input="form.acta = normalizarActa(form.acta)"
                       :class="errores.acta && 'border-red-400'">
            </div>

            <div>
                <label class="tw-label" for="tipo_trabajo">Tipo de trabajo</label>
                <select class="tw-select" id="tipo_trabajo" x-model="form.tipoTrabajo"
                        :class="errores.tipoTrabajo && 'border-red-400'">
                    <option value="">Seleccione tipo de trabajo</option>
                    @foreach ([
                        'FI-29 revisión periódica línea matriz', 'RP 10444', 'RP 12161',
                        'RN 12162', 'SA 12163', 'SA 12164',
                    ] as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="tw-label" for="contrato">Contrato</label>
                {{-- Prefijo ":" fijo, solo dígitos después, máximo 18. --}}
                <input type="text" class="tw-input" id="contrato" x-model="form.contrato"
                       @focus="form.contrato = normalizarContrato(form.contrato)"
                       @input="form.contrato = normalizarContrato(form.contrato)"
                       :class="errores.contrato && 'border-red-400'">
            </div>

            {{-- Estas dos se ocultan para línea matriz, igual que antes. --}}
            <div x-show="!esLineaMatriz" x-cloak>
                <label class="tw-label" for="orden_trabajo">Orden de trabajo</label>
                <input type="text" class="tw-input" id="orden_trabajo" x-model="form.orden"
                       @input="form.orden = form.orden.replace(/[^0-9]/g, '').slice(0, 18)"
                       :class="errores.orden && 'border-red-400'">
            </div>

            <div x-show="!esLineaMatriz" x-cloak>
                <label class="tw-label" for="categoria">Categoría</label>
                <select class="tw-select" id="categoria" x-model="form.categoria"
                        :class="errores.categoria && 'border-red-400'">
                    <option value="">Seleccione categoría</option>
                    <option value="RESIDENCIAL">RESIDENCIAL</option>
                    <option value="COMERCIAL">COMERCIAL</option>
                </select>
            </div>

            <div>
                <label class="tw-label" for="hora_inicio">Hora inicio</label>
                <input type="time" class="tw-input" id="hora_inicio" step="60" x-model="form.horaInicio"
                       :class="errores.horaInicio && 'border-red-400'">
            </div>

            <div>
                <label class="tw-label" for="hora_final">Hora final</label>
                <input type="time" class="tw-input" id="hora_final" step="60" x-model="form.horaFinal"
                       :class="errores.horaFinal && 'border-red-400'">
            </div>

            <div x-show="!esLineaMatriz" x-cloak>
                <label class="tw-label" for="recintos">¿4 recintos o más?</label>
                <select class="tw-select" id="recintos" x-model="form.recintos"
                        @change="if (form.recintos !== 'SI') form.cantidadRecintos = ''">
                    <option value="NO">NO</option>
                    <option value="SI">SÍ</option>
                </select>
            </div>

            <div x-show="!esLineaMatriz" x-cloak>
                <label class="tw-label" for="NroRecintosP">Cantidad de recintos</label>
                <input type="text" inputmode="numeric" class="tw-input text-center" id="NroRecintosP"
                       :disabled="form.recintos !== 'SI'"
                       x-model="form.cantidadRecintos"
                       @input="form.cantidadRecintos = form.cantidadRecintos.replace(/[^0-9]/g, '').slice(0, 3)"
                       :class="errores.cantidadRecintos && 'border-red-400'">
            </div>

            <div class="sm:col-span-2 lg:col-span-3">
                <label class="tw-label" for="resultado_cierre">Resultado de cierre</label>
                <select class="tw-select" id="resultado_cierre" x-model="form.resultado"
                        :class="errores.resultado && 'border-red-400'">
                    <option value="">Seleccione cierre</option>
                    <option value="CERTIFICADA">CERTIFICADA</option>
                    <option value="CERTIFICADA CON NOVEDADES">CERTIFICADA CON NOVEDADES</option>
                    <option value="INSPECCIONADA CON DEFECTO CRITICO VALLE">INSPECCIONADA CON DEFECTO CRÍTICO VALLE</option>
                    <option value="INSPECCIONADA CON DEFECTO NO CRITICO VALLE">INSPECCIONADA CON DEFECTO NO CRÍTICO VALLE</option>
                </select>
            </div>
        </div>

        <p class="tw-hint mt-4" x-show="form.horaInicio && form.horaFinal">
            <i class="fas fa-stopwatch"></i>
            Duración calculada: <strong x-text="duracionCalculada"></strong>
        </p>

        <x-pqrs.error message="errorAgregar" class="mt-4" />
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="modal = 'dia'">Cerrar</button>
        <button type="button" class="tw-btn-primary" @click="agregarInspeccion()" :disabled="agregando">
            <i class="fas" :class="agregando ? 'fa-spinner fa-spin' : 'fa-plus'"></i> Agregar
        </button>
    </x-slot:footer>
</x-modal>
