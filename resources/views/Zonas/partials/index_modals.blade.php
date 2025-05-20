{{-- Modal para crear Municipio --}}

<div class="modal fade" id="municipioModal" tabindex="-1" role="dialog" aria-labelledby="crearMunicipioModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearMunicipioModalLabel">Ingresar Municipio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" class="form-control" id="nombreMunicipio" name="nombre">
                    <input type="hidden" id="idGuardarMunicipio">
                </div>
                <div class="form-group">
                    <label for="sede">Sede</label>
                    <select class="form-control" name="sede" id="sedeMunicipio">
                        <option value="">Seleccione una sede</option>
                        @foreach ($sedes as $sede)
                            <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="zona">Zona</label>
                    <select class="form-control" name="zona" id="zonaMunicipio">
                        <option value="">Seleccione una zona</option>
                        @foreach ($zonas as $zona)
                            <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="submit" id="crearMunicipio" class="btn btn-primary">Crear Municipio</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal para crear Barrios --}}

<div class="modal fade" id="barrioModal" tabindex="-1" role="dialog" aria-labelledby="crearBarrioModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearBarrioModalLabel">Ingresar Barrio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" class="form-control" id="barrio" name="nombre">
                    <input type="hidden" id="idGuardarBarrio">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="submit" id="crearBarrio" class="btn btn-primary">Crear Municipio</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal para crear Grupos --}}

<div class="modal fade" id="grupoModal" tabindex="-1" role="dialog" aria-labelledby="crearGrupoModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearGrupoModalLabel">Ingresar Grupo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="grupo">Nombre</label>
                    <input type="text" class="form-control" id="grupo" name="grupo">
                    <input type="hidden" id="idGuardarGrupo">
                </div>
                <div class="form-group">
                    <label for="sede">Sede</label>
                    <select class="form-control" name="sede" id="sedeGrupo">
                        <option value="">Seleccione una sede</option>
                        @foreach ($sedes as $sede)
                            <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="submit" id="crearGrupo" class="btn btn-primary">Crear Grupo</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal para crear Sub Grupos --}}

<div class="modal fade" id="subGrupoModal" tabindex="-1" role="dialog" aria-labelledby="crearSubGrupoModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearSubGrupoModalLabel">Ingresar Sub Grupo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="subgrupo">Nombre</label>
                    <input type="text" class="form-control" id="subgrupo" name="subgrupo">
                    <input type="hidden" id="idGuardarSubGrupo">
                </div>
                <div class="form-group">
                    <label for="sede">Sede</label>
                    <select class="form-control" name="sede" id="sedeSubGrupo">
                        <option value="">Seleccione una sede</option>
                        @foreach ($sedes as $sede)
                            <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="submit" id="crearSubGrupo" class="btn btn-primary">Crear Sub Grupo</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de asignacion de grupos --}}

<div class="modal fade" id="AsignadorModal" aria-labelledby="asignadorModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="asignadorModalLabel">Asignador</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3 font-weight-bold">Municipio</div>
                    <div class="col-md-3 font-weight-bold">Grupo</div>
                    <div class="col-md-3 font-weight-bold">Sub Grupo</div>
                    <div class="col-md-3 font-weight-bold">Barrios Disponibles</div>
                </div>
                <div id="selectores-container">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="submit" id="asignarGrupo" class="btn btn-primary">Guardar</button>

            </div>
        </div>
        <div class="modal-footer">
        </div>
    </div>
</div>

<!-- Modal para Sedes y Zonas -->

<div class="modal fade" id="extraCardsModal" tabindex="-1" role="dialog" aria-labelledby="extraCardsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="extraCardsModalLabel">Gestionar Sedes y Zonas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">

                    <!-- Tarjeta Sedes -->

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Sedes</h3>
                            </div>
                            <div class="card-body">
                                <a class="btn btn-primary mb-2" id="btnCrearSede" data-toggle="modal" data-target="#sedeModal">Crear Sede</a>
                                <table class="table table-striped" id="sedes">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sedes as $sede)
                                        <tr data-id="{{$sede->id}}">
                                            <td>{{ $sede->nombre }}</td>
                                            <td>
                                                <div style="display: flex; gap: 5px; justify-content: center;">
                                                    <button class="btn btn-info btn-sm abrirSedeModal" data-sede-id="{{ $sede->id }}">Editar</button>
                                                    @if ($sede->status == 1)
                                                        <button class="btn btn-danger btn-sm" id="btnChangeStatusSede" data-sede-id="{{ $sede->id }}">Desactivar</button>
                                                    @else
                                                        <button class="btn btn-success btn-sm" id="btnChangeStatusSede" data-sede-id="{{ $sede->id }}">Activar</button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <input type="hidden" id="cambiarEstadoSede" value="{{route('cortes_produccion.changeStatusSede')}}">
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta Zonas -->

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Zonas</h3>
                            </div>
                            <div class="card-body">
                                <a class="btn btn-primary mb-2" id="btnCrearZona" data-toggle="modal" data-target="#zonaModal">Crear Zona</a>
                                <table class="table table-striped" id="zonas">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($zonas as $zona)
                                        <tr data-id="{{$zona->id}}">
                                            <td>{{ $zona->nombre }}</td>
                                            <td>
                                                <div style="display: flex; gap: 5px; justify-content: center;">
                                                    <button class="btn btn-info btn-sm abrirZonaModal" data-zona-id="{{ $zona->id }}">Editar</button>
                                                    @if ($zona->status == 1)
                                                        <button class="btn btn-danger btn-sm" id="btnChangeStatusZona" data-zona-id="{{ $zona->id }}">Desactivar</button>
                                                    @else
                                                        <button class="btn btn-success btn-sm" id="btnChangeStatusZona" data-zona-id="{{ $zona->id }}">Activar</button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <input type="hidden" id="cambiarEstadoZona" value="{{route('cortes_produccion.changeStatusZona')}}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales de Crear Sede y Crear Zona -->
<div>

    {{-- Modal para crear Sede --}}

    <div class="modal fade" id="sedeModal" tabindex="-1" role="dialog" aria-labelledby="crearSedeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearSedeModalLabel">Ingresar Sede</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombreSede">Nombre</label>
                        <input type="text" class="form-control" id="nombreSede">
                        <input type="hidden" id="idGuardarSede">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" id="crearSede" class="btn btn-primary">Crear Sede</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para crear Zona --}}

    <div class="modal fade" id="zonaModal" tabindex="-1" role="dialog" aria-labelledby="crearZonaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearZonaModalLabel">Ingresar Zona</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombreZona">Nombre</label>
                        <input type="text" class="form-control" id="nombreZona">
                        <input type="hidden" id="idGuardarZona">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" id="crearZona" class="btn btn-primary">Crear Zona</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Asignacion de responsables subgrupos --}}
<div class="modal fade" id="ResponsablesModal" tabindex="-1" role="dialog" aria-labelledby="ResponsablesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ResponsableModalLabel">Gestión de asignación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="responsables-modal-body">
                <div class="text-center">
                    <span class="spinner-border spinner-border-sm"></span> Cargando...
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="MasivaModal" tabindex="-1" role="dialog" aria-labelledby="MasivaModalLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="MasivaModalLabel">Inserción Masiva</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="masiva-modal-body">
              <input type="file" id="archivo_masivo" class="form-control">
                <br>
                <div  style="display: none" id="cargando_masiva">
                    <span class="spinner-border spinner-border-sm"></span> Cargando...
                </div>
                <div class="row" id="messageMasivas"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn-procesar">Procesar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

