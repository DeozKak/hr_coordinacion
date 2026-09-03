@extends('layouts.tw.app')

@section('title', 'Asignación planilla')

@section('content_header')
    <h1>Asignación planilla</h1>
@endsection

@section('subtitle', 'Genera la planilla de un inspector en Excel, en PDF o en ambos.')

@section('content')
    <div class="mx-auto max-w-2xl"
         x-data="{
            inspector: '',
            parametro: '',
            tipoOrden: '',
            fecha: '',
            excel: false,
            pdf: false,
            error: '',

            /* 'Marca' no admite ni tipo de orden ni fecha; 'Fecha' admite las dos;
               'Todo' admite el tipo de orden pero no la fecha. */
            get verTipoOrden() { return this.parametro !== '2'; },
            get verFecha() { return this.parametro === '1'; },

            /* Las mismas dos comprobaciones que hace el servidor, que responde
               con una redirección: hacerlas aquí evita recargar la página. */
            valido() {
                this.error = '';
                if (!this.inspector) { this.error = 'Por favor seleccione un inspector.'; return false; }
                if (!this.excel && !this.pdf) { this.error = 'Por favor seleccione un método de exporte.'; return false; }
                return true;
            },
         }">

        <form id="formPlanilla" method="get" action="{{ route('generarExcelPdf') }}" autocomplete="off"
              @submit="if (!valido()) $event.preventDefault()"
              class="tw-card">

            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-file-lines"></i></span>
                    <div>
                        <h2 class="tw-card-title">Planilla</h2>
                        <p class="tw-card-subtitle">Elige el inspector y qué quieres descargar.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 2xl:space-y-5 p-4 2xl:p-5">
                <div>
                    <label class="tw-label" for="inspectorPlanilla">Inspector</label>
                    <select class="tw-select" name="inspectorPlanilla" id="inspectorPlanilla" x-model="inspector">
                        <option value="">Seleccione…</option>
                        @foreach ($inspectors as $inspector)
                            {{-- El valor sigue siendo el id; lo que cambia es que ahora
                                 la lista muestra también el nombre, no sólo el número. --}}
                            <option value="{{ $inspector->id }}">
                                {{ $inspector->id }}. {{ $inspector->apellidos }} {{ $inspector->nombres }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="tw-label" for="parametro">Parámetro</label>
                        <select class="tw-select" name="parametro" id="parametro" x-model="parametro">
                            <option value="">Todo</option>
                            <option value="1">Fecha</option>
                            <option value="2">Marca</option>
                        </select>
                    </div>

                    <div x-show="verTipoOrden" x-cloak x-transition.opacity>
                        <label class="tw-label" for="tipoOrden">Tipo de orden</label>
                        <select class="tw-select" name="tipoOrden" id="tipoOrden" x-model="tipoOrden">
                            <option value="">Ambas</option>
                            <option value="1">Masiva</option>
                            <option value="2">Externa</option>
                        </select>
                    </div>

                    <div x-show="verFecha" x-cloak x-transition.opacity>
                        <label class="tw-label" for="fechaAsignacion">Fecha</label>
                        <input class="tw-input" type="date" name="fechaAsignacion" id="fechaAsignacion"
                               x-model="fecha">
                    </div>
                </div>

                <div>
                    <span class="tw-label">Exportar</span>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            ['campo' => 'expExcel', 'modelo' => 'excel', 'etiqueta' => 'Excel', 'icono' => 'fa-file-excel'],
                            ['campo' => 'expPdf',   'modelo' => 'pdf',   'etiqueta' => 'PDF',   'icono' => 'fa-file-pdf'],
                        ] as $exp)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3.5
                                          transition hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700/40"
                                   :class="{{ $exp['modelo'] }} && 'border-brand-400 bg-brand-50/50 dark:border-brand-500 dark:bg-brand-900/20'">
                                <input type="checkbox" name="{{ $exp['campo'] }}" id="{{ $exp['campo'] }}"
                                       x-model="{{ $exp['modelo'] }}"
                                       class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <i class="fas {{ $exp['icono'] }} text-slate-400"></i>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                    {{ $exp['etiqueta'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <p x-show="error" x-cloak
                   class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                          dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                   x-text="error"></p>
            </div>

            <div class="flex justify-end border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/60">
                <button type="submit" id="btnGenerarPdfExcel" class="tw-btn-primary">
                    <i class="fas fa-download"></i> Generar
                </button>
            </div>
        </form>
    </div>
@endsection
