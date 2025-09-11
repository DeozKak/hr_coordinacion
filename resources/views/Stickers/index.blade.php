@extends('adminlte::page')

@section('title', 'Stickers')

@section('content_header')
    <h1><i class="fa fa-sticky-note"></i> Control Stickers</h1>
@stop

@section('content')

    <link rel="stylesheet" href="{{ asset('css/stickers/indexV2.1.css')}}">
    <input type="hidden" id="url_ActualizarInventario"
           value="{{ route('bitacora.stickers.ActualizarInventario',['id'=>':id']) }}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <input type="hidden" id="urlGetInventario" value="{{ route('bitacora.stickers.getInventario') }}">
    <input type="hidden" id="urlAsignarSticker" value="{{ route('bitacora.stickers.asignar') }}">
    <input type="hidden" id="urlDesasignarSticker" value="{{ route('bitacora.stickers.desasignar') }}">
    <input type="hidden" id="getStickersAsignados" value="{{ route('bitacora.stickers.getStickersAsignados',['idInspector' => ':id']) }}">

    <div class="container">
        <div class="card-custom">
            <div class="card-header-actions">
                <h2>Inventario General</h2>
                @can('control_stickers')
                    <button type="button" class="btn btn-gradient btn-gradient-success" id="btnAgregarSticker">
                        <i class="fa fa-plus"></i> Agregar a Inventario
                    </button>
                @endcan
            </div>

            <div class="inventory-grid">
                @foreach($Stickers as $sticker)
                    <div class="inventory-item">
                        @php
                            $s = strtolower($sticker->nombre);
                            $badgeClass = 'bg-primary';
                            $iconClass = 'fa-circle';
                            if ($s == 'amarillos') { $badgeClass = 'bg-warning text-dark'; }
                            if ($s == 'rojos') { $badgeClass = 'bg-danger'; }
                            if ($s == 'suspension') { $badgeClass = 'bg-secondary'; $iconClass = 'fa-ban'; }
                            if ($s == 'cons de visita') { $badgeClass = 'bg-primary'; }
                            if ($s == 'isometricos') { $badgeClass = 'bg-brown'; }
                        @endphp
                        <span class="badge {{ $badgeClass }} p-2">
                        <i class="fa {{ $iconClass }}"></i> {{ $sticker->nombre }}
                    </span>
                        <div class="count" id="inventario_{{ strtolower($sticker->id) }}">
                            {{ optional($sticker->Inventario)->cantidad_disponible ?? 0 }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="section-title">
                <i class="fa fa-user-check text-primary"></i> Asignación por Inspector
            </div>

            <div class="table-responsive">
                <table class="table" id="semanas">
                    <thead>
                    <tr>
                        <th><i class="fa fa-user"></i> Inspector</th>
                        <th><i class="fa fa-sticky-note"></i> Stickers Asignados</th>
                        <th><i class="fa fa-calendar-alt"></i> Historial de Entregas</th>
                        @can('control_stickers')
                            <th><i class="fa fa-cogs"></i> Acciones</th>
                        @endcan
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($inspectores as $inspector)
                        <tr>
                            <td>{{ $inspector->nombre_completo }}</td>
                            <td>
                                <div class="sticker-count-group">
                                    @foreach($Stickers as $stickerTipo)
                                        <div class="sticker-item">
                                            @php
                                                $nombreClave = strtolower($stickerTipo->nombre);
                                                $dotClass = 'dot-' . $nombreClave;

                                                if (!in_array($dotClass, ['dot-amarillos', 'dot-rojos', 'dot-suspension', 'dot-cons de visita', 'dot-isometricos'])) {
                                                    $dotClass = 'dot-default';
                                                }
                                            @endphp
                                            <span class="sticker-dot {{ $dotClass }}"></span>
                                            <span>
                                            {{ optional($inspector->Stickers->firstWhere('id_sticker_tipo', $stickerTipo->id))->cantidad_asignada ?? 0 }}
                                        </span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <div class="history-group">
                                    @forelse($inspector->HistoricoStickers->sortByDesc('fecha_asignacion') as $registro)
                                        <div class="history-item">
                                            <b>{{ \Carbon\Carbon::parse($registro->fecha_asignacion)->format('d-m-Y') }}</b>
                                            @php
                                                $nombreTipo = strtolower(optional($registro->stickerTipo ?? $Stickers->firstWhere('id', $registro->id_sticker_tipo))->nombre);
                                                $dotClass = 'dot-' . $nombreTipo;
                                                if (!in_array($dotClass, ['dot-amarillos', 'dot-rojos', 'dot-suspension', 'dot-cons de visita', 'dot-isometricos'])) {
                                                    $dotClass = 'dot-default';
                                                }
                                            @endphp
                                            <span class="sticker-dot {{ $dotClass }}"></span>
                                            <span>{{ $registro->cantidad }} ({{ ucfirst($nombreTipo) }})</span>
                                        </div>
                                    @empty
                                        <span style="color:#aaa;">Sin historial</span>
                                    @endforelse
                                </div>
                            </td>
                            @can('control_stickers')
                                <td>
                                    <div class="buttons-container" style="justify-content: flex-start;">
                                        <button class="btn-gradient btn-gradient-success btn-sm" onclick="asignarSticker('{{ $inspector->id }}', '{{ $inspector->nombre_completo }}')">
                                            <i class="fa fa-plus"></i> Asignar
                                        </button>
                                        <button class="btn-gradient btn-gradient-danger btn-sm" onclick="desasignarSticker('{{ $inspector->id }}')">
                                            <i class="fa fa-minus"></i> Desasignar
                                        </button>
                                    </div>
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>


    @include('stickers.modales.modales')

    @section('js')
        <script src="{{ asset('js/stickers/index.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('js/stickers/AsignacionStickers.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('js/stickers/DesasgnarStickers.js') }}?v={{ time() }}"></script>

    @stop
@stop



