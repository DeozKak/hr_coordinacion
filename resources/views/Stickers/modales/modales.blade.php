<!-- Modal Desasignar Stickers Múltiples -->
<div class="modal fade" id="modalDesasignarSticker" tabindex="-1" aria-labelledby="modalDesasignarStickerLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <form id="formDesasignarSticker">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDesasignarStickerLabel">
                        <i class="fa fa-minus-circle text-danger"></i> Desasignar stickers de <span id="nombreInspectorDesasignar"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idInspectorDesasignar" name="idInspectorDesasignar">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        Selecciona la cantidad de stickers que deseas desasignar. Los stickers serán devueltos al inventario.
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
                                    <span class="badge bg-info">
                                        Asignado: <span id="asignado-{{ $tipo->id }}">0</span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="errorDesasignar" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn_cerrarDesasignar">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-minus"></i> Desasignar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Agregar Stickers  -->
<div class="modal fade" id="agregarStickerModal" tabindex="-1" aria-labelledby="agregarStickerLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <form id="formAgregarSticker" autocomplete="off">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarStickerLabel">
                        <i class="fa fa-plus-circle"></i> Agregar stickers al inventario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">

                    <div class="alert alert-danger d-none" id="errorAgregar"></div>

                    <div class="mb-3">
                        <label class="form-label" for="tipoSticker">Tipo de sticker</label>
                        <select class="form-select" id="tipoSticker" name="tipoSticker" required>
                            <option value="">Seleccionar...</option>
                            @foreach($Stickers as $sticker)
                                <option value="{{$sticker->id}}">{{$sticker->nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cantidad">Cantidad a agregar</label>
                        <input type="text" class="form-control" id="cantidad" name="cantidad" min="1"
                               placeholder="Ej: 50" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnCancelarSticker">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- /Modal -->

<!-- Modal Asignar Stickers Múltiples -->
<div class="modal fade" id="modalAsignarSticker" tabindex="-1" aria-labelledby="modalAsignarStickerLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <form id="formAsignarSticker">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAsignarStickerLabel">
                        Asignar stickers a <span id="nombreInspector"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
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
                                <span class="badge bg-secondary">
                                    Saldo: <span
                                        id="saldo-{{ $tipo->id }}">{{ $tipo->Inventario->cantidad_disponible ?? 0 }}</span>
                                </span>
                                </div>
                            </div>
                        @endforeach

                    </div>
                    <div id="errorAsignar" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn_cerrarAsignar">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Asignar</button>
                </div>
            </div>
        </form>
    </div>
</div>
