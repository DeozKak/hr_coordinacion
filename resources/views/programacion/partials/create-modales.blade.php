{{-- ===================== AÑADIR EN PLANTILLA ============================ --}}
<x-modal show="modal === 'plantilla'" close="cerrarPlantilla()" size="max-w-4xl"
         icon="fa-file-lines" tint="blue" title="Programación en plantilla"
         subtitle="Registro manual, sin buscar en la base">

    <div class="grid gap-4 px-5 py-5 sm:grid-cols-2">

        {{-- Contrato --}}
        <div>
            <label class="tw-label" for="CONTRATO"><i class="fas fa-file-signature"></i> Contrato</label>
            <input type="text" id="CONTRATO" class="tw-input" :class="claseCampo('CONTRATO')"
                   x-model="plantilla.CONTRATO"
                   @input="plantilla.CONTRATO = soloDigitos($event.target.value, 15)">
        </div>

        {{-- Tipo de trabajo --}}
        <div>
            <label class="tw-label" for="TIPO_TRABAJO"><i class="fas fa-screwdriver-wrench"></i> Tipo de trabajo</label>
            <select id="TIPO_TRABAJO" class="tw-select" :class="claseCampo('TIPO_TRABAJO')"
                    x-model="plantilla.TIPO_TRABAJO">
                <option value="">Seleccione tipo de trabajo</option>
                <option value="FI-29 revisión periódica línea matriz">FI-29 revisión periódica línea matriz</option>
                <option value="10444">RP 10444</option>
                <option value="12161">RP 12161</option>
                <option value="12162">RN 12162</option>
                <option value="12163">SA 12163</option>
                <option value="12164">SA 12164</option>
            </select>
        </div>

        {{-- Fecha (hoy, fija) --}}
        <div>
            <label class="tw-label" for="FECHA"><i class="fas fa-calendar-day"></i> Fecha</label>
            <input type="date" id="FECHA" class="tw-input" x-model="plantilla.FECHA" readonly>
        </div>

        {{-- Celular --}}
        <div>
            <label class="tw-label" for="CELULAR"><i class="fas fa-mobile-screen"></i> Celular</label>
            <input type="text" id="CELULAR" class="tw-input" :class="claseCampo('CELULAR')"
                   inputmode="numeric" x-model="plantilla.CELULAR"
                   @input="plantilla.CELULAR = soloDigitos($event.target.value, 10)">
            <p class="tw-hint">Diez dígitos.</p>
        </div>

        {{-- Nombre usuario --}}
        <div>
            <label class="tw-label" for="NOMBRE_USUARIO"><i class="fas fa-user"></i> Nombre usuario</label>
            <input type="text" id="NOMBRE_USUARIO" class="tw-input" :class="claseCampo('NOMBRE_USUARIO')"
                   x-model="plantilla.NOMBRE_USUARIO"
                   @input="plantilla.NOMBRE_USUARIO = soloLetras($event.target.value, 50)">
        </div>

        {{-- Orden de trabajo --}}
        <div>
            <label class="tw-label" for="ORDEN_TRABAJO"><i class="fas fa-hashtag"></i> Orden de trabajo</label>
            <input type="text" id="ORDEN_TRABAJO" class="tw-input text-center"
                   x-model="plantilla.ORDEN_TRABAJO"
                   @input="plantilla.ORDEN_TRABAJO = soloDigitos($event.target.value, 18)">
            <p class="tw-hint">Si lo dejas vacío se guarda como N/A.</p>
        </div>

        {{-- Dirección --}}
        <div>
            <label class="tw-label" for="DIRECCION"><i class="fas fa-map-location-dot"></i> Dirección</label>
            <input type="text" id="DIRECCION" class="tw-input" :class="claseCampo('DIRECCION')"
                   x-model="plantilla.DIRECCION">
        </div>

        {{-- Barrio --}}
        <div>
            <label class="tw-label" for="BARRIO"><i class="fas fa-location-dot"></i> Barrio</label>
            <input type="text" id="BARRIO" class="tw-input" :class="claseCampo('BARRIO')"
                   x-model="plantilla.BARRIO">
        </div>

        {{-- Municipio (buscador contra municipios.json) --}}
        <div x-data="{ abierto: false }" @click.outside="abierto = false" class="relative">
            <label class="tw-label" for="CIUDAD"><i class="fas fa-city"></i> Municipio</label>
            <input type="text" id="CIUDAD" class="tw-input" :class="claseCampo('CIUDAD')"
                   placeholder="Escribe al menos 2 letras…" autocomplete="off"
                   x-model="municipioTexto"
                   @focus="abierto = true"
                   @input="abierto = true; plantilla.CIUDAD = ''; buscarMunicipios()">
            <i class="fas fa-spinner fa-spin absolute right-3 top-[38px] text-sm text-slate-400"
               x-show="buscandoMunicipios" x-cloak></i>

            <ul x-show="abierto && municipios.length" x-cloak
                class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200
                       bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800">
                <template x-for="m in municipios" :key="m">
                    <li>
                        <button type="button"
                                class="w-full px-3 py-2 text-left text-sm hover:bg-slate-100
                                       dark:hover:bg-slate-700"
                                @click="plantilla.CIUDAD = m; municipioTexto = m; abierto = false"
                                x-text="m"></button>
                    </li>
                </template>
            </ul>
        </div>

        {{-- Estado --}}
        <div>
            <span class="tw-label"><i class="fas fa-toggle-on"></i> Estado</span>
            <div class="flex items-center gap-5 pt-1.5">
                @foreach ([['activo', 'Activo'], ['suspendido', 'Suspendido']] as [$valor, $texto])
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="radio" name="estado" value="{{ $valor }}" x-model="plantilla.estado"
                               class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500
                                      dark:border-slate-600 dark:bg-slate-700">
                        {{ $texto }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Categoría --}}
        <div>
            <label class="tw-label" for="CATEGORIA"><i class="fas fa-tag"></i> Categoría</label>
            <select id="CATEGORIA" class="tw-select" :class="claseCampo('CATEGORIA')"
                    x-model="plantilla.CATEGORIA">
                <option value="">Seleccione categoría</option>
                <option value="RESIDENCIAL">RESIDENCIAL</option>
                <option value="COMERCIAL">COMERCIAL</option>
            </select>
        </div>

        {{-- Fecha agendamiento --}}
        <div>
            <label class="tw-label" for="FECHA_AGENDAMIENTO">
                <i class="fas fa-calendar-check"></i> Fecha agendamiento
            </label>
            <input type="date" id="FECHA_AGENDAMIENTO" class="tw-input" :class="claseCampo('FECHA_AGENDAMIENTO')"
                   :min="manana" x-model="plantilla.FECHA_AGENDAMIENTO">
        </div>

        {{-- Observaciones --}}
        <div class="sm:col-span-2">
            <label class="tw-label" for="OBSERVACIONES"><i class="fas fa-eye"></i> Observaciones</label>
            <input type="text" id="OBSERVACIONES" class="tw-input" :class="claseCampo('OBSERVACIONES')"
                   maxlength="200" x-model="plantilla.OBSERVACIONES">
        </div>

        {{-- Por qué se programó (fijo: el usuario en sesión) --}}
        <div class="sm:col-span-2">
            <label class="tw-label" for="PORQUE_PROGRAMO">
                <i class="fas fa-circle-question"></i> Por qué se programó
            </label>
            <input type="text" id="PORQUE_PROGRAMO" class="tw-input" maxlength="200"
                   x-model="plantilla.PORQUE_PROGRAMO" readonly>
        </div>

        {{-- Inspector --}}
        <div>
            <label class="tw-label" for="TECNICO"><i class="fas fa-user-gear"></i> Inspector</label>
            <select id="TECNICO" class="tw-select" :class="claseCampo('TECNICO')" x-model="plantilla.TECNICO">
                <option value="">Seleccione inspector</option>
                <template x-for="t in tecnicos" :key="t">
                    <option :value="t" x-text="t.replace(/^\d+\.\s*/, '')"></option>
                </template>
            </select>
        </div>

        {{-- Jornada --}}
        <div>
            <label class="tw-label" for="JORNADA"><i class="fas fa-clock"></i> Jornada</label>
            <select id="JORNADA" class="tw-select" :class="claseCampo('JORNADA')" x-model="plantilla.JORNADA">
                <option value="">Seleccione jornada</option>
                @foreach (['mañana', 'tarde', 'todo el dia'] as $jornada)
                    <option value="{{ $jornada }}">{{ $jornada }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="cerrarPlantilla()">Cerrar</button>
        <button type="button" class="tw-btn-primary" :disabled="enviandoPlantilla" @click="agregarPlantilla()">
            <i class="fas" :class="enviandoPlantilla ? 'fa-spinner fa-spin' : 'fa-plus'"></i> Agregar
        </button>
    </x-slot:footer>
</x-modal>

{{-- ===================== OBSERVACIÓN COMPLETA =========================== --}}
<x-modal show="modal === 'verMas'" close="modal = null" size="max-w-2xl"
         icon="fa-circle-info" tint="sky" title="Información completa">
    <div class="px-5 py-5">
        <p class="whitespace-pre-wrap break-words rounded-xl border border-slate-200 bg-slate-50 p-4
                  text-sm leading-relaxed text-slate-700
                  dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300"
           x-text="verMas"></p>
    </div>

    <x-slot:footer>
        <button type="button" class="tw-btn-secondary" @click="modal = null">Cerrar</button>
    </x-slot:footer>
</x-modal>
