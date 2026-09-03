@extends('layouts.tw.app')

@section('title', 'Bitácoras')
@section('content_header')
    <h1>Bitácora</h1>
@endsection
@section('subtitle', 'Revisa, ajusta y guarda las inspecciones por inspector.')

@php
    use Illuminate\Support\Carbon;

    $datos = $response->toArray();

    /* Duración: misma regla que tenía la vista (si la hora final es menor,
       se asume que cruzó la medianoche) y el umbral de 20 minutos. */
    $duracionDe = function ($ini, $fin) {
        if (! $ini || ! $fin) return ['texto' => '', 'minutos' => null];
        $a = DateTime::createFromFormat('H:i', $ini);
        $b = DateTime::createFromFormat('H:i', $fin);
        if (! $a || ! $b) return ['texto' => '', 'minutos' => null];
        if ($b < $a) $b->add(new DateInterval('P1D'));
        $d = $a->diff($b);
        return ['texto' => $d->format('%H:%I'), 'minutos' => $d->h * 60 + $d->i];
    };

    /* Una tabla por inspector, en el mismo orden que $nombres: el índice de
       tabla es la clave `select_{T}_{n}` que espera guardar_tabla(). */
    $tablas = [];
    foreach ($nombres as $i => $nombre) {
        $filas = [];
        foreach (array_filter($datos, fn ($r) => $r['CC_OPERARIO'] === $cedulas[$i]) as $r) {
            $dur = $duracionDe($r['HORA_INICIO'] ?? null, $r['HORA_FINAL'] ?? null);
            $recintos = $r['4_RECINTOS'];
            $filas[] = [
                'id'            => (string) $r['id'],
                'nombre'        => (string) $r['NOMBRE'],
                'cedula'        => (string) $r['CC_OPERARIO'],
                'municipio'     => (string) $r['MUNICIPIO'],
                'fecha'         => (string) $r['FECHA'],
                'acta'          => (string) $r['No_ACTA'],
                'tipo'          => (string) $r['TIPO_TRABAJO'],
                'contrato'      => (string) $r['CONTRATO'],
                'orden'         => (string) $r['ORDEN_TRABAJO'],
                'ordenExt'      => (string) $r['ORDEN_EXT'],
                'categoria'     => (string) $r['CATEGORIA'],
                'resultado'     => (string) $r['RESULTADO_CIERRE'],
                'horaInicio'    => (string) ($r['HORA_INICIO'] ?? ''),
                'horaFinal'     => (string) ($r['HORA_FINAL'] ?? ''),
                'duracion'      => $dur['texto'],
                'duracionMin'   => $dur['minutos'],
                'tieneRecintos' => $recintos !== 'NO',
                'recintos'      => $recintos !== 'NO' ? (string) $recintos : '',
                'estado'        => (string) $r['ESTADO'],
                'causal'        => (string) $r['CAUSAL'],
                'vence'         => (string) $r['vence'],
                'rechazo'       => '',
                'periodoGracia' => (string) $r['PERIODO_GRACIA'],
                'gDevolucion'   => (int) $r['G_DEVOLUCION'] === 1,
                'gracia'        => (int) $r['PERIODO_GRACIA'] === 1,
                'vence60'       => $r['vence'] === '60 meses',
                'nueva'         => false,
            ];
        }
        $tablas[] = ['nombre' => $nombre, 'cedula' => $cedulas[$i], 'filas' => $filas];
    }
@endphp

@section('content')
<div x-data="bitacoraTabla({
        tablas: @js($tablas),
        causales: @js(collect($causales)->pluck('nom_causal')->values()),
        inspectores: @js(collect($inspectores)->map(fn ($i) => [
            'cedula' => $i->cedula,
            'nombre' => trim($i->apellidos.' '.$i->nombres),
        ])->values()),
        idBitacora: @js($datos[0]['id_bitacora'] ?? null),
        idSuper: @js($id_super),
        urls: {
            actualizar: @js(route('bitacoras.actualizar', ['id' => ':id'])),
            agregar:    @js(route('bitacoras.agregar')),
            guardar:    @js(route('bitacoras.guardar_tabla', ['super' => $id_super])),
            municipios: @js(route('municipios.json')),
        },
     })"
     class="space-y-4 2xl:space-y-6">

    {{-- ============ BARRA DE CONTROL ============ --}}
    <section class="tw-card">
        <div class="tw-card-header">
            <label class="flex min-w-0 flex-1 items-center gap-3">
                <span class="tw-label mb-0 shrink-0">Personal</span>
                <select x-model.number="indiceActivo" class="tw-select max-w-md">
                    <template x-for="(t, i) in tablas" :key="t.nombre">
                        <option :value="i" x-text="`${t.nombre} (${t.filas.length})`"></option>
                    </template>
                </select>
            </label>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('bitacora') }}" class="tw-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Ir atrás
                </a>
                <button type="button" @click="abrirPapel()" class="tw-btn-secondary">
                    <i class="fas fa-file-circle-plus"></i> Inspección en papel
                </button>
                <button type="button" @click="guardar()" :disabled="guardando" class="tw-btn-primary">
                    <i class="fas" :class="guardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="guardando ? 'Guardando…' : 'Guardar'"></span>
                </button>
            </div>
        </div>
    </section>

    {{-- ============ INDICADORES DEL INSPECTOR ACTIVO ============ --}}
    <section class="grid gap-4 2xl:gap-5 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['clave' => 'certificada',     'label' => 'Certificada',              'icon' => 'fa-circle-check',        'tint' => 'emerald'],
            ['clave' => 'conNovedades',    'label' => 'Certificada con novedades','icon' => 'fa-circle-exclamation',  'tint' => 'amber'],
            ['clave' => 'defectoCritico',  'label' => 'Defecto crítico',          'icon' => 'fa-triangle-exclamation','tint' => 'rose'],
            ['clave' => 'defectoNoCritico','label' => 'Defecto no crítico',       'icon' => 'fa-circle-info',         'tint' => 'sky'],
            ['clave' => 'total',           'label' => 'Total contratos OK',       'icon' => 'fa-list-check',          'tint' => 'blue'],
        ] as $kpi)
            <div class="tw-card p-4 2xl:p-5">
                <div class="flex items-start justify-between gap-3">
                    <span class="tw-eyebrow max-w-[9rem]">{{ $kpi['label'] }}</span>
                    <span class="tw-chip chip-{{ $kpi['tint'] }}"><i class="fas {{ $kpi['icon'] }}"></i></span>
                </div>
                <p class="tw-metric mt-4" x-text="indicadores.{{ $kpi['clave'] }}"></p>
            </div>
        @endforeach
    </section>

    {{-- ============ TABLA DEL INSPECTOR ACTIVO ============ --}}
    <section class="tw-card">
        <div class="tw-card-header">
            <div class="flex items-center gap-3">
                <span class="tw-chip chip-blue"><i class="fas fa-table-list"></i></span>
                <div>
                    <h2 class="tw-card-title" x-text="tablaActiva.nombre"></h2>
                    <p class="tw-card-subtitle" x-text="`${tablaActiva.filas.length} inspecciones`"></p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-fuchsia-300"></span> Vence 60 meses</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-lime-300"></span> Periodo de gracia</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-300"></span> Gestión devolución</span>
            </div>
        </div>

        <div class="max-h-[35rem] tw-card-scroll">
            <table class="tw-table whitespace-nowrap tw-table-fija">
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th>ID</th><th>Inspector</th><th>CC operario</th><th>Municipio</th><th>Fecha</th>
                        <th>N° acta</th><th>Tipo de trabajo</th><th>Contrato</th><th>Orden trabajo</th>
                        <th>Orden ext</th><th>Categoría</th><th>Resultado cierre</th>
                        <th>Hora inicio</th><th>Hora final</th><th>Duración</th>
                        <th>4 recintos o más</th><th>Estado</th><th>Causal</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(f, i) in tablaActiva.filas" :key="f.id">
                        <tr :class="{
                                'bg-fuchsia-50 dark:bg-fuchsia-950/30': f.vence60,
                                'bg-lime-50 dark:bg-lime-950/30': f.gracia,
                                'bg-rose-50 dark:bg-rose-950/30': f.gDevolucion,
                            }">
                            <td class="text-slate-400" x-text="f.id"></td>
                            <td class="font-medium text-slate-800 dark:text-slate-200" x-text="f.nombre"></td>
                            <td class="tabular-nums" x-text="f.cedula"></td>
                            <td x-text="f.municipio"></td>
                            <td class="tabular-nums" x-text="f.fecha"></td>
                            <td class="tabular-nums" x-text="f.acta"></td>
                            <td x-text="f.tipo"></td>

                            {{-- El contrato marca el estado de la fila: verde OK, rojo DV --}}
                            <td class="font-semibold tabular-nums"
                                :class="f.estado === 'DV'
                                    ? 'bg-rose-200 text-rose-900 dark:bg-rose-900/60 dark:text-rose-100'
                                    : 'bg-emerald-200 text-emerald-900 dark:bg-emerald-900/60 dark:text-emerald-100'"
                                x-text="f.contrato"></td>

                            <td class="tabular-nums" x-text="f.orden"></td>
                            <td class="tabular-nums" x-text="f.ordenExt"></td>
                            <td :class="f.categoria === 'COMERICAL' && 'bg-amber-200 text-amber-900 dark:bg-amber-900/60 dark:text-amber-100'"
                                x-text="f.categoria"></td>
                            <td x-text="f.resultado"></td>
                            <td class="tabular-nums" x-text="f.horaInicio"></td>
                            <td class="tabular-nums" x-text="f.horaFinal"></td>
                            <td class="tabular-nums"
                                :class="f.duracionMin !== null && f.duracionMin <= 20
                                    && 'bg-amber-200 font-semibold text-amber-900 dark:bg-amber-900/60 dark:text-amber-100'"
                                x-text="f.duracion"></td>

                            {{-- 4 recintos: la casilla habilita la cantidad --}}
                            <td>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" x-model="f.tieneRecintos"
                                           @change="alternarRecintos(f)"
                                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    <input type="text" inputmode="numeric" size="3"
                                           class="tw-input w-16 px-2 py-1 text-center"
                                           x-model="f.recintos"
                                           :disabled="!f.tieneRecintos"
                                           @input="f.recintos = f.recintos.replace(/\D/g, '').slice(0, 3)"
                                           @change="guardarCampo(f, '4_RECINTOS', f.recintos)">
                                </div>
                            </td>

                            <td>
                                <select class="tw-select w-24 py-1.5" x-model="f.estado"
                                        @change="cambiarEstado(f)">
                                    <option value="OK">OK</option>
                                    <option value="DV">DV</option>
                                </select>
                            </td>

                            <td>
                                <select class="tw-select w-64 py-1.5"
                                        x-show="f.estado === 'DV'" x-cloak
                                        x-model="f.causal"
                                        @change="guardarCampo(f, 'CAUSAL', f.causal)">
                                    <template x-for="c in causales" :key="c">
                                        <option :value="c" x-text="c"></option>
                                    </template>
                                </select>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!tablaActiva.filas.length">
                        <td colspan="18" class="py-12 text-center text-slate-400">
                            Este inspector no tiene inspecciones en la bitácora.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    @include('bitacoras.partials.tabla-modal-papel')
</div>
@endsection

@section('js')
    @include('bitacoras.partials.tabla-script')
@endsection
