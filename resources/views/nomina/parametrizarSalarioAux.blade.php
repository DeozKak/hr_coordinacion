@extends('layouts.tw.app')

@section('title', 'Salario mínimo y auxilio de transporte')

@section('content_header')
    <h1>Salario mínimo y auxilio de transporte</h1>
@endsection

@section('subtitle', 'Parámetros de nómina por periodo: base salarial y porcentajes de aportes.')

@section('actions')
    <a href="{{ route('nomina.reporteNomina') }}" class="tw-btn-secondary">
        <i class="fas fa-arrow-left"></i> Ir a nómina
    </a>
@endsection

@section('content')
    <div x-data="parametrosNomina({
            registros: {{ Js::from($parametroSalarioAux->map(fn ($p) => [
                'id' => $p->id,
                'fecha_inicio' => $p->fecha_inicio,
                'fecha_fin' => $p->fecha_fin,
                'salario_minimo' => (int) $p->salario_minimo,
                'auxilio_transporte' => (int) $p->auxilio_transporte,
                'salud' => (float) $p->salud,
                'pension' => (float) $p->pension,
                'arl' => (float) $p->arl,
                'caja' => (float) $p->caja,
                'prima' => (float) $p->prima,
                'cesantias' => (float) $p->cesantias,
                'intCesantias' => (float) $p->intCesantias,
                'vacaciones' => (float) $p->vacaciones,
            ])->values()) }},
            urls: {
                guardar:    '{{ route('nomina.guardarSalarioAux') }}',
                actualizar: '{{ route('nomina.actualizarSalarioAux') }}',
            },
         })"
         class="space-y-6">

        {{-- =============================== FORMULARIO ======================== --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip" :class="editando ? 'chip-amber' : 'chip-blue'">
                        <i class="fas" :class="editando ? 'fa-pen' : 'fa-money-check-dollar'"></i>
                    </span>
                    <div>
                        <h2 class="tw-card-title"
                            x-text="editando ? `Editar el periodo #${form.id}` : 'Nuevo periodo'"></h2>
                        <p class="tw-card-subtitle">
                            Todos los campos son obligatorios. El periodo no puede solaparse con otro.
                        </p>
                    </div>
                </div>

                <button type="button" class="tw-btn-secondary" x-show="editando" x-cloak
                        @click="cancelarEdicion()">
                    <i class="fas fa-xmark"></i> Cancelar edición
                </button>
            </div>

            <div class="space-y-5 px-5 py-5">
                <template x-if="error">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                                dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                         x-text="error"></div>
                </template>

                {{-- Periodo y base salarial --}}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="tw-label" for="fechaSalAuxInicio">Desde</label>
                        <input type="month" id="fechaSalAuxInicio" class="tw-input"
                               :class="claseCampo('fecha_inicio')"
                               x-model="form.fecha_inicio" :max="form.fecha_fin || null">
                    </div>
                    <div>
                        <label class="tw-label" for="fechaSalAuxFin">Hasta</label>
                        <input type="month" id="fechaSalAuxFin" class="tw-input"
                               :class="claseCampo('fecha_fin')"
                               x-model="form.fecha_fin" :min="form.fecha_inicio || null">
                    </div>
                    <div>
                        <label class="tw-label" for="salMin">Salario mínimo</label>
                        @include('nomina.partials.campo-dinero',
                                 ['id' => 'salMin', 'campo' => 'salario_minimo'])
                    </div>
                    <div>
                        <label class="tw-label" for="auxTrans">Auxilio de transporte</label>
                        @include('nomina.partials.campo-dinero',
                                 ['id' => 'auxTrans', 'campo' => 'auxilio_transporte'])
                    </div>
                </div>

                {{-- Porcentajes de aportes y prestaciones --}}
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="tw-badge chip-emerald">
                            <i class="fas fa-percent"></i> Aportes y prestaciones
                        </span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            ['Salud', 'salud'], ['Pensión', 'pension'], ['ARL', 'arl'],
                            ['Caja', 'caja'], ['Prima', 'prima'], ['Cesantías', 'cesantias'],
                            ['Int. cesantías', 'intCesantias'], ['Vacaciones', 'vacaciones'],
                        ] as [$etiqueta, $campo])
                            <div>
                                <label class="tw-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                                @include('nomina.partials.campo-porcentaje',
                                         ['id' => $campo, 'campo' => $campo])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                        dark:border-slate-700/60">
                <button type="button" class="tw-btn-secondary" @click="limpiar()">
                    <i class="fas fa-eraser"></i> Limpiar
                </button>
                <button type="button" class="tw-btn-primary" :disabled="enviando" @click="enviar()">
                    <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="editando ? 'Guardar cambios' : 'Guardar'"></span>
                </button>
            </div>
        </section>

        {{-- ================================ LISTADO ========================== --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-slate"><i class="fas fa-list"></i></span>
                    <div>
                        <h2 class="tw-card-title">Periodos parametrizados</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="filtrados.length"></span> de
                            <span x-text="registros.length"></span>
                        </p>
                    </div>
                </div>

                <div class="relative w-full sm:w-64">
                    <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2
                              -translate-y-1/2 text-sm text-slate-400"></i>
                    <input type="search" class="tw-input pl-9" placeholder="Buscar por periodo…"
                           x-model="busqueda">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tw-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Periodo</th>
                        <th class="text-right">Salario mínimo</th>
                        <th class="text-right">Aux. transporte</th>
                        @foreach (['Salud', 'Pensión', 'ARL', 'Caja', 'Prima',
                                   'Cesantías', 'Int. ces.', 'Vacaciones'] as $t)
                            <th class="text-right">{{ $t }}</th>
                        @endforeach
                        <th class="text-right">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="p in filtrados" :key="p.id">
                        <tr :class="editando && form.id === p.id
                                ? 'bg-amber-50 dark:bg-amber-950/30' : ''">
                            <td class="font-mono text-xs text-slate-500 dark:text-slate-400" x-text="p.id"></td>
                            <td class="whitespace-nowrap font-medium text-slate-800 dark:text-slate-100"
                                x-text="`${p.fecha_inicio} → ${p.fecha_fin}`"></td>
                            <template x-for="campo in DINERO" :key="campo">
                                <td class="whitespace-nowrap text-right tabular-nums"
                                    x-text="moneda(p[campo])"></td>
                            </template>
                            <template x-for="campo in PORCENTAJES" :key="campo">
                                <td class="whitespace-nowrap text-right tabular-nums"
                                    x-text="porcentaje(p[campo])"></td>
                            </template>
                            <td class="text-right">
                                <button type="button" class="tw-btn-secondary tw-btn-sm" @click="editar(p)">
                                    <i class="fas fa-pen"></i> Editar
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filtrados.length === 0" x-cloak>
                        <td colspan="13" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            <i class="fas fa-money-check-dollar mb-2 block text-2xl opacity-40"></i>
                            No hay periodos parametrizados.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@section('js')
    @include('nomina.partials.parametros-script')
@endsection
