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
