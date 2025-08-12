@extends('adminlte::page')

@section('title', 'Stickers')

@section('content_header')
    <h1><i class="fa fa-sticky-note"></i> Control Stickers</h1>
@stop


@section('content')

    <link rel="stylesheet" href="{{ asset('css/stickers/index.css')}}">
    <input type="hidden" id="url_ActualizarInventario"
           value="{{ route('bitacora.stickers.ActualizarInventario',['id'=>':id']) }}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <input type="hidden" id="urlGetInventario" value="{{ route('bitacora.stickers.getInventario') }}">
    <input type="hidden" id="urlAsignarSticker" value="{{ route('bitacora.stickers.asignar') }}">
    <input type="hidden" id="urlDesasignarSticker" value="{{ route('bitacora.stickers.desasignar') }}">
    <input type="hidden" id="getStickersAsignados" value="{{ route('bitacora.stickers.getStickersAsignados',['idInspector' => ':id']) }}">

    <div class="container">
        <div class="card card-custom">
            <div>
                @can('control_stickers')
                <button type="button" class="btn btn-success btn-add-inventory" id="btnAgregarSticker">
                    <i class="fa fa-plus"></i> Agregar stickers a Inventario
                </button>
                @endcan
            </div>
            <div class="inventory-header">
                <i class="fa fa-archive text-primary"></i>
                Inventario de Stickers
            </div>
            <table class="table table-bordered table-hover table-inventory mb-4">
                <thead class="table-light">
                <tr>
                    @foreach($Stickers as $sticker)
                        @switch($sticker->nombre)
                            @case('AMARILLOS')
                                <th>
                <span class="badge bg-warning text-dark p-2">
                    <i class="fa fa-circle"></i> Amarillos
                </span>
                                </th>
                                @break

                            @case('ROJOS')
                                <th>
                <span class="badge bg-danger p-2">
                    <i class="fa fa-circle"></i> Rojos
                </span>
                                </th>
                                @break

                            @case('SUSPENSION')
                                <th>
                <span class="badge bg-secondary p-2">
                    <i class="fa fa-ban"></i> Suspensión
                </span>
                                </th>
                                @break

                            @default
                                <th>
                <span class="badge bg-primary p-2">
                    <i class="fa fa-circle"></i> {{$sticker->nombre}}
                </span>
                                </th>
                        @endswitch
                    @endforeach
                </tr>
                </thead>
                <tbody>
                <tr>
                    @foreach($Stickers as $sticker)
                        <td id="inventario_{{ strtolower($sticker->id) }}">
                            {{ optional($sticker->Inventario)->cantidad_disponible ?? 0 }}
                        </td>
                    @endforeach

                </tr>
                </tbody>
            </table>



            <div class="section-title">
                <i class="fa fa-user-check text-success"></i> Stickers por inspector
            </div>
            <div class="row justify-content-center shadow-container">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped" id="semanas">
                        <thead class="table-light">
                        <tr>
                            <th><i class="fa fa-user"></i> Nombres y apellidos</th>
                            <th><i class="fa fa-sticky-note"></i> Stickers por tipo</th>
                            <th><i class="fa fa-calendar-alt"></i> Última fecha de entrega</th>
                            @can('control_stickers')
                            <th><i class="fa fa-cogs"></i> Acción</th>
                            @endcan
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($inspectores as $inspector)

                            <tr>
                                <td>{{ $inspector->nombre_completo }}</td>

                                <td>
                                    @foreach($Stickers as $stickerTipo)
                                        @php
                                            // Puedes usar el nombre tal cual, en minúsculas para buscar en 'stickers_por_tipo'
                                            $nombreClave = strtolower($stickerTipo->nombre);

                                            // Asigna el color según el nombre, puedes ajustar esto como gustes
                                            switch($nombreClave) {
                                                case 'amarillos':
                                                    $color = "#fdbd00";
                                                    break;
                                                case 'rojos':
                                                    $color = "#f21b35";
                                                    break;
                                                case 'suspension':
                                                case 'gris':
                                                    $color = "#69747c";
                                                    break;
                                                default:
                                                    $color = "#4d5863";
                                            }
                                        @endphp
                                        <span style="display: inline-flex; align-items: center; margin-right: 10px;">
        <span
            style="width:14px; height:14px; border-radius:50%; background-color:{{ $color }}; display:inline-block; margin-right:2px;"></span>
        {{-- Si tienes stickers_por_tipo, úsalos. Si no, usa la lógica de la respuesta anterior. --}}
                                            {{
                      optional(
                          $inspector->Stickers->firstWhere('id_sticker_tipo', $stickerTipo->id)
                      )->cantidad_asignada ?? 0
                  }}

    </span>

                                    @endforeach
                                </td>


                                <td>  @php
                                        // Configura los colores disponibles para cada tipo reconocible
                                        $tiposColores = [
                                            'amarillos' => '#FDBD00FF',
                                            'rojos'     => '#F21B35FF',
                                            'suspension'     => '#69747CFF'
                                        ];
                                    @endphp

                                    @if($inspector->HistoricoStickers->isNotEmpty())
                                        {{-- Agrupa por fecha/hora si quieres, o solo muestra cada registro --}}
                                        @foreach($inspector->HistoricoStickers->sortByDesc('fecha_asignacion') as $registro)
                                            <div style="margin-bottom:8px; font-size:0.95em;">
                                                <b>
                                                    {{ \Carbon\Carbon::parse($registro->fecha_asignacion)->format('d-m-Y H:i') }}
                                                </b>
                                                @php
                                                    // Busca el tipo, asumiendo relación o que puedes llegar al modelo de tipo
                                                    $nombreTipo = strtolower(optional($registro->stickerTipo ?? $Stickers->firstWhere('id', $registro->id_sticker_tipo))->nombre);
                                                    $color = $tiposColores[$nombreTipo] ?? '#4d5863';
                                                @endphp

                                                <span style="display:inline-flex; align-items:center; margin-left:8px;">
                <span
                    style="width:14px; height:14px; border-radius:50%; background:{{ $color }}; display:inline-block; margin-right:2px;"></span>
                {{ $registro->cantidad }} ({{ ucfirst($nombreTipo) }})
            </span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span style="color:#aaa;">Sin historial</span>
                                    @endif

                                </td>
                                @can('control_stickers')
                                <td>

                                    <button class="btn btn-success btn-sm"
                                            onclick="asignarSticker('{{ $inspector->id }}', '{{ $inspector->nombre_completo }}')">
                                        <i class="fa fa-plus"></i> Asignar
                                    </button>


                                    <button class="btn btn-danger btn-sm"
                                            onclick="desasignarSticker('{{ $inspector->id }}')">
                                        <i class="fa fa-minus"></i> Desasignar
                                    </button>

                                </td>
                                @endcan
                            </tr>
                        @endforeach
                        </tbody>

                    </table>
                </div>
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



