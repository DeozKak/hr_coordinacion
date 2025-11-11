{{-- ================================================================= --}}
{{-- MODAL AGREGAR STICKERS A INVENTARIO --}}
{{-- ================================================================= --}}
<div class="modal fade" id="agregarStickerModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel"><i class="fa fa-plus-circle"></i> Agregar Stickers a Inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAgregarSticker">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tipoSticker">Tipo de Sticker</label>
                        <select class="form-control" id="tipoSticker">
                            <option value="">-- Seleccione un tipo --</option>
                            {{-- Asumo que la variable $Stickers está disponible aquí, si no, hay que pasarla --}}
                            @foreach($Stickers as $sticker)
                                <option value="{{ $sticker->id }}" data-nombre="{{ strtolower($sticker->nombre) }}">
                                    {{ $sticker->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CAMPO PARA CANTIDAD (Stickers normales) --}}
                    <div class="form-group" id="campo_cantidad">
                        <label for="cantidad">Cantidad a Agregar</label>
                        <input type="number" class="form-control" id="cantidad" placeholder="Ej: 50" min="1" max="10000">
                    </div>

                    {{-- CAMPOS PARA SERIALES (Solo Actas) --}}
                    <div id="campo_seriales" class="d-none">
                        <div class="form-group">
                            <label for="serial_inicio">Serial Inicial</label>
                            <input type="number" class="form-control" id="serial_inicio" placeholder="Ej: 1001">
                        </div>
                        <div class="form-group">
                            <label for="serial_fin">Serial Final</label>
                            <input type="number" class="form-control" id="serial_fin" placeholder="Ej: 1100">
                        </div>
                    </div>

                    <div id="errorAgregar" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnCancelarSticker" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-plus"></i> Agregar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================================================================= --}}
{{-- MODAL ASIGNAR STICKERS --}}
{{-- ================================================================= --}}
<div class="modal fade" id="modalAsignarSticker" tabindex="-1" role="dialog" aria-labelledby="modalLabelAsignar"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabelAsignar"><i class="fa fa-user-plus"></i> Asignar Stickers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAsignarSticker">
                <div class="modal-body">
                    <input type="hidden" id="idInspector">
                    <p>Asignando a: <strong id="nombreInspector"></strong></p>

                    <div id="stickerTypeRows">
                        <table class="table table-sm table-bordered">
                            <thead>
                            <tr>
                                <th>Tipo de Sticker</th>
                                <th>Entrada (Cantidad o Rango)</th>
                                <th>Inventario Disponible</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($Stickers as $sticker)
                                <tr>
                                    <td>
                                            <span class="badge" style="background-color: {{ $sticker->color_hex ?? '#6c757d' }}; color: #fff;">
                                                {{ $sticker->nombre }}
                                            </span>
                                    </td>

                                    {{-- LÓGICA CONDICIONAL: Si es ACTA muestra seriales, si no, cantidad --}}
                                    @if(strtolower($sticker->nombre) == 'actas')
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" class="form-control" id="acta_serial_inicio" placeholder="Serial Inicial">
                                                <span class="input-group-text">-</span>
                                                <input type="number" class="form-control" id="acta_serial_fin" placeholder="Serial Final">
                                            </div>
                                        </td>
                                    @else
                                        <td>
                                            <input type="number"
                                                   class="form-control form-control-sm cantidad-sticker"
                                                   name="stickers[{{ $sticker->id }}]"
                                                   data-id="{{ $sticker->id }}"
                                                   data-inventario="{{ optional($sticker->Inventario)->cantidad_disponible ?? 0 }}"
                                                   placeholder="Cantidad"
                                                   min="0">
                                        </td>
                                    @endif

                                    {{-- Columna de Saldo/Inventario --}}
                                    <td class="text-center">
                                        @if(strtolower($sticker->nombre) == 'actas')
                                            <span class="badge bg-info" id="saldo-{{ $sticker->id }}">
                                                    {{ optional($sticker->Inventario)->cantidad_disponible ?? 0 }}
                                                </span>
                                        @else
                                            <span class="badge bg-info" id="saldo-{{ $sticker->id }}">
                                                    {{ optional($sticker->Inventario)->cantidad_disponible ?? 0 }}
                                                </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div id="errorAsignar" class="alert alert-danger d-none mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn_cerrarAsignar" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Asignar</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ================================================================= --}}
{{-- MODAL DESASIGNAR STICKERS --}}
{{-- ================================================================= --}}
<div class="modal fade" id="modalDesasignarSticker" tabindex="-1" role="dialog" aria-labelledby="modalLabelDesasignar"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabelDesasignar"><i class="fa fa-user-minus"></i> Desasignar Stickers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDesasignarSticker">
                <div class="modal-body">
                    <input type="hidden" id="idInspectorDesasignar">
                    <p>Desasignando de: <strong id="nombreInspectorDesasignar"></strong></p>

                    <div id="stickerTypeRowsDesasignar">
                        <table class="table table-sm table-bordered">
                            <thead>
                            <tr>
                                <th>Tipo de Sticker</th>
                                <th>Entrada (Cantidad o Rango)</th>
                                <th>Asignado Actualmente</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($Stickers as $sticker)
                                <tr>
                                    <td>
                                            <span class="badge" style="background-color: {{ $sticker->color_hex ?? '#6c757d' }}; color: #fff;">
                                                {{ $sticker->nombre }}
                                            </span>
                                    </td>

                                    {{-- LÓGICA CONDICIONAL: Si es ACTA muestra seriales, si no, cantidad --}}
                                    @if(strtolower($sticker->nombre) == 'actas')
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" class="form-control cantidad-sticker-desasignar acta"  data-id="{{ $sticker->id }}" id="desasignar_acta_serial_inicio" placeholder="Serial Inicial">
                                                <span class="input-group-text">-</span>
                                                <input type="number" class="form-control" id="desasignar_acta_serial_fin" placeholder="Serial Final">
                                            </div>
                                        </td>
                                    @else
                                        <td>
                                            <input type="number"
                                                   class="form-control form-control-sm cantidad-sticker-desasignar"
                                                   name="stickers[{{ $sticker->id }}]"
                                                   data-id="{{ $sticker->id }}"
                                                   data-asignado="0" {{-- Se llenará con JS --}}
                                                   placeholder="Cantidad a devolver"
                                                   min="0">
                                        </td>
                                    @endif

                                    {{-- Columna de Asignado --}}
                                    <td class="text-center">
                                            <span class="badge bg-success" id="asignado-{{ $sticker->id }}">
                                                0
                                            </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div id="errorDesasignar" class="alert alert-danger d-none mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn_cerrarDesasignar" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-minus"></i> Desasignar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================================================================= --}}
{{-- MODAL VER SERIALES DE ACTAS EN INVENTARIO --}}
{{-- ================================================================= --}}
<div class="modal fade" id="modalVerSerialesActa" tabindex="-1" role="dialog" aria-labelledby="modalLabelSeriales"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabelSeriales"><i class="fa fa-list-ol"></i> Seriales de Actas en Inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Mostrando rangos de seriales disponibles:</p>

                {{-- Aquí se cargarán los seriales vía JS --}}
                <div id="listaSerialesBody" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Cargando...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- ================================================================= --}}
{{-- MODAL VER SERIALES DE ACTAS ASIGNADOS A INSPECTOR --}}
{{-- ================================================================= --}}
<div class="modal fade" id="modalVerSerialesAsignados" tabindex="-1" role="dialog" aria-labelledby="modalLabelSerialesAsignados"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabelSerialesAsignados"><i class="fa fa-user-check"></i> Seriales Asignados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Seriales de Actas asignados a: <strong id="nombreInspectorSeriales"></strong></p>

                {{-- Aquí se cargarán los seriales vía JS --}}
                <div id="listaSerialesAsignadosBody" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center" id="loaderSerialesAsignados">
                        <i class="fa fa-spinner fa-spin"></i> Cargando...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
