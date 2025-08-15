<!-- Modal Detalles de día Rediseñado -->
<div class="modal fade modal-detalle-inspeccion" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content shadow-container">
            <div class="modal-header border-0">
                <div class="d-flex align-items-center w-100">
                    <div class="icon-circle bg-primary me-2">
                        <i class="fas fa-search text-white"></i>
                    </div>
                    <h5 class="modal-title flex-grow-1" id="titulo">Inspecciones</h5>
                    <span class="badge bg-danger" id="cantidadDobles" style="font-size:1em; margin-right: 20px;"></span>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
            </div>
            <div class="modal-body">
                <div id="mensajeNoDatos" class="alert alert-warning mb-4 d-none">
                    <i class="fas fa-exclamation-triangle me-1"></i>No hay datos disponibles
                </div>
                {{-- <div class="row mb-2">
                     <div class="col-12">
                         <h6 class="text-primary fw-semibold mb-2"><i class="fas fa-chart-bar me-1"></i> Cantidad Prioridades</h6>
                         <div id="contadores_dia" class="mb-3"></div>
                     </div>
                 </div>--}}
                <div class="row mb-1">
                    <div class="col-12">
                        <h6 class="text-success fw-semibold mb-2"><i class="fas fa-clipboard-list me-1"></i> Inspecciones</h6>
                        <div id="contratos_dia"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-between">
                <div>
                    @haspermission('ver_residente')
                    <button type="button" class="btn btn-primary shadow-sm" id="agregar">
                        <i class="fas fa-plus"></i> Agregar Inspección
                    </button>
                    @endhaspermission
                </div>
                <button type="button" class="btn btn-outline-secondary" id="cerrar_modal" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Contar Dobles Sábado Rediseñado -->
<div class="modal fade modal-contar-dobles" id="modalContarDoblesSabado" tabindex="-1" aria-labelledby="contarDoblesLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content shadow-container">
            <div class="modal-header border-0">
                <div class="d-flex align-items-center w-100">
                    <div class="icon-circle bg-success me-2">
                        <i class="fas fa-calculator text-white"></i>
                    </div>
                    <h5 class="modal-title flex-grow-1" id="contarDoblesLabel">Contar dobles</h5>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label for="contarSabado" class="fw-semibold">
                        Inspecciones a contar
                        <span class="text-danger inspeccionesTotales"></span>
                    </label>
                    <input class="form-control inputNumeric" type="number" id="contarSabado" min="0">
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-end">
                <button type="button" class="btn btn-outline-secondary" id="btn_modal_cerrar">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary btnGuardarContarSabado"
                        data-url="{{route('produccion.countDoublesSaturday')}}">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Inspección -->
<div class="modal fade" id="ventanaEmergente" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    <i class="fas fa-plus-circle"></i> Agregar Inspección
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formularioInspeccion">
                    <!-- Sección Inspector y Municipio -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre">
                                    <i class="fas fa-user"></i> Inspector:
                                </label>
                                <select class="form-control" name="nombre" id="nombre" disabled>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="municipio">
                                    <i class="fas fa-map-marker-alt"></i> Municipio:
                                </label>
                                <select class="form-control select2" name="municipio" id="municipio-select"></select>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Fecha y N° Acta -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha">
                                    <i class="fas fa-calendar"></i> Fecha:
                                </label>
                                <input type="date" class="form-control" name="fecha" id="fecha" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="N°acta">
                                    <i class="fas fa-file-alt"></i> N° ACTA
                                </label>
                                <input type="text" class="form-control" name="N°acta" id="N°acta">
                            </div>
                        </div>
                    </div>

                    <!-- Sección Tipo de Trabajo y Contrato -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_trabajo">
                                    <i class="fas fa-tools"></i> Tipo de Trabajo
                                </label>
                                <select class="form-control" name="tipo_trabajo" id="tipo_trabajo">
                                    <option value="">Seleccione Tipo de Trabajo</option>
                                    <option value="FI-29 revisión periódica línea matriz">FI-29 revisión periódica línea matriz</option>
                                    <option value="RP 10444">RP 10444</option>
                                    <option value="RP 12161">RP 12161</option>
                                    <option value="RN 12162">RN 12162</option>
                                    <option value="SA 12163">SA 12163</option>
                                    <option value="SA 12164">SA 12164</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contrato">
                                    <i class="fas fa-handshake"></i> Contrato
                                </label>
                                <input type="text" class="form-control" name="contrato" id="contrato" value=":">
                            </div>
                        </div>
                    </div>

                    <!-- Sección Orden de trabajo y Categoría -->
                    <div class="row matriz-des1">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="orden_trabajo">
                                    <i class="fas fa-list-ol"></i> Orden de trabajo
                                </label>
                                <input type="text" class="form-control" name="orden_trabajo" id="orden_trabajo">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="categoria">
                                    <i class="fas fa-tags"></i> Categoría
                                </label>
                                <select class="form-control" name="categoria" id="categoria">
                                    <option value="">Seleccione categoría</option>
                                    <option value="RESIDENCIAL">RESIDENCIAL</option>
                                    <option value="COMERCIAL">COMERCIAL</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Horarios -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="hora_inicio">
                                    <i class="fas fa-clock"></i> Hora Inicio
                                </label>
                                <input type="time" class="form-control" name="hora_inicio" id="hora_inicio" step="60" pattern="[0-9]{2}:[0-9]{2}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="hora_final">
                                    <i class="fas fa-clock"></i> Hora Final
                                </label>
                                <input type="time" class="form-control" name="hora_final" id="hora_final" step="60" pattern="[0-9]{2}:[0-9]{2}">
                            </div>
                        </div>
                    </div>

                    <!-- Sección Recintos -->
                    <div class="row matriz-des2">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recintos">
                                    <i class="fas fa-building"></i> 4 Recintos o más
                                </label>
                                <select class="form-control" name="recintos" id="recintos">
                                    <option value="NO" selected>NO</option>
                                    <option value="SI">SÍ</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cantidad_recintos">
                                    <i class="fas fa-hashtag"></i> Cantidad de recintos
                                </label>
                                <input type="number" class="form-control text-center" id="NroRecintosP" disabled min="0">
                            </div>
                        </div>
                    </div>

                    <!-- Sección Resultado -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="resultado_cierre">
                                    <i class="fas fa-check-circle"></i> Resultado Cierre
                                </label>
                                <select class="form-control" name="resultado_cierre" id="resultado_cierre">
                                    <option value="">Seleccione Cierre</option>
                                    <option value="CERTIFICADA">CERTIFICADA</option>
                                    <option value="CERTIFICADA CON NOVEDADES">CERTIFICADA CON NOVEDADES</option>
                                    <option value="INSPECCIONADA CON DEFECTO CRITICO VALLE">INSPECCIONADA CON DEFECTO CRÍTICO VALLE</option>
                                    <option value="INSPECCIONADA CON DEFECTO NO CRITICO VALLE">INSPECCIONADA CON DEFECTO NO CRÍTICO VALLE</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" id="agregarInspeccion">
                    <i class="fas fa-plus"></i> Agregar
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
