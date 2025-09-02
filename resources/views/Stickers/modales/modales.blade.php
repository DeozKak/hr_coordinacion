<!-- Modal Desasignar Stickers Múltiples -->
<div class="modal fade modal-modern" id="modalDesasignarSticker" tabindex="-1">
    <div class="modal-dialog">
        <form id="formDesasignarSticker">
            <div class="modal-content">
                <div class="modal-header">
                    {{-- Nueva estructura para el título --}}
                    <div>
                        <h5 class="modal-title">
                            <i class="fa fa-minus-circle text-danger"></i>
                            <span>Desasignar Stickers</span>
                        </h5>
                        {{-- El nombre del inspector ahora es un subtítulo --}}
                        <div class="modal-subtitle">
                             <span id="nombreInspectorDesasignar"></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idInspectorDesasignar" name="idInspectorDesasignar">
                    <div class="alert-modern alert-info-modern">
                        <i class="fa fa-info-circle"></i>
                        Selecciona la cantidad de stickers que deseas desasignar. Los stickers serán devueltos al
                        inventario.
                    </div>
                    <div id="stickerTypeRowsDesasignar">
                        @foreach($Stickers as $tipo)
                            <div class="mb-2 row align-items-center">
                                <label class="col-sm-3 col-form-label">{{ $tipo->nombre }}</label>
                                <div class="col-sm-3">
                                    <input
                                        type="text"
                                        min="0"
                                        class="form-control cantidad-sticker-desasignar"
                                        name="stickers[{{ $tipo->id }}]"
                                        data-id="{{ $tipo->id }}"
                                        data-asignado="0"
                                        placeholder="Cantidad"
                                    >
                                </div>
                                <div class="col-sm-3">
                                     <span class="badge-modern badge-info-modern">
                                    Asignado: <span id="asignado-{{ $tipo->id }}">0</span>
                                </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="errorDesasignar" class="alert-modern alert-danger-modern d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn_cerrarDesasignar" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-gradient btn-gradient-danger">
                        <i class="fa fa-minus"></i> Desasignar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Agregar Stickers  -->
<div class="modal fade modal-modern" id="agregarStickerModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="formAgregarSticker" autocomplete="off">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-plus-circle text-success"></i> Agregar stickers al inventario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="alert-modern alert-danger-modern d-none" id="errorAgregar"></div>

                    <div class="mb-3">
                        <label class="form-label" for="tipoSticker">Tipo de sticker</label>
                        <select class="form-select" id="tipoSticker" name="tipoSticker" >
                            <option value="">Seleccionar...</option>
                            @foreach($Stickers as $sticker)
                                <option value="{{$sticker->id}}">{{$sticker->nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cantidad">Cantidad a agregar</label>
                        <input type="text" class="form-control" id="cantidad" name="cantidad" min="1"
                               placeholder="Ej: 50" >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnCancelarSticker" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-gradient btn-gradient-success">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- /Modal -->

<!-- Modal Asignar Stickers Múltiples -->
<div class="modal fade modal-modern" id="modalAsignarSticker" tabindex="-1">
    <div class="modal-dialog">
        <form id="formAsignarSticker">
            <div class="modal-content">
                <div class="modal-header">
                    {{-- Nuevo grupo para título y subtítulo --}}
                    <div class="modal-title-group">
                        <h5 class="modal-title">
                            <i class="fa fa-check-circle text-primary"></i>
                            <span>Asignar Stickers</span>
                        </h5>
                        <div class="modal-subtitle" id="nombreInspector">
                            {{-- El nombre se carga aquí con JS --}}
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idInspector" name="idInspector">
                    <div id="stickerTypeRows">
                        @foreach($Stickers as $tipo)
                            <div class="mb-2 row align-items-center">
                                <label class="col-sm-3 col-form-label">{{ $tipo->nombre }}</label>
                                <div class="col-sm-3">
                                    <input
                                        type="text"
                                        min="0"
                                        max="{{ $tipo->Inventario->cantidad_disponible ?? 0 }}"
                                        class="form-control cantidad-sticker"
                                        name="stickers[{{ $tipo->id }}]"
                                        data-id="{{ $tipo->id }}"
                                        data-inventario="{{ $tipo->Inventario->cantidad_disponible ?? 0 }}"
                                        placeholder="Cantidad"
                                    >
                                </div>

                                <div class="col-sm-3">
                               <span class="badge-modern badge-secondary-modern">
                                    Saldo: <span id="saldo-{{ $tipo->id }}">{{ $tipo->Inventario->cantidad_disponible ?? 0 }}</span>
                                </span>
                                </div>
                            </div>
                        @endforeach

                    </div>
                    <div id="errorAsignar" class="alert-modern alert-danger-modern d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn_cerrarAsignar" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-gradient btn-gradient-success">
                        <i class="fa fa-check"></i> Asignar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
