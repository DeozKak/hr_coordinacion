@extends('adminlte::page')

@section('title', 'Bitácoras')

@section('content_header')
    <h1>Bitácoras</h1>
@endsection

@section('content')

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="{{asset('css/bitacoras/TablasV2.css')}}">
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="{{asset('js/bitacora/tbl_dinamicaV8.js')}}"></script>
    </head>


    <body>
    <div id="miContenedor" data-id-super="{{ json_encode($id_super) }}"></div>
    <div id="loader" style="display: none;"></div>
    <div id="overlay" style="display: none;"></div>
    <input type="hidden" id="token" name="csrf_token" value="{{ csrf_token() }}">
    <input type="hidden" id="url_actualizar" name="route" value="{{ route('bitacoras.actualizar',['id' => ':id']) }}">
    <input type="hidden" id="url_agregar" name="route_agregar" value="{{ route('bitacoras.agregar') }}">
    <input type="hidden" id="url_guardar" name="route_guardar"
           value="{{ route('bitacoras.guardar_tabla',['super' => $id_super]) }}">
    <input type="hidden" id="url_borrar" name="route_borrar" value="{{ route('bitacoras.borrar_archivos') }}">
    <div class="shadow-container">
        {{-- <button class="btn btn-primary" id="btnPapel">Agregar Inspeccion en Papel</button>--}}
        <div class="card-header-controls">
            <div class="selector-container">
                <label for="selectorPersonal">Personal:</label>
                <select id="selectorPersonal" class="form-control-modern">
                    <?php foreach ($nombres as $index => $nombre) : ?>
                    <option value="<?= $nombre; ?>"><?= $nombre; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="buttons-container">
                <a class="btn-back" href="{{route('bitacora')}}">
                    <i class="fas fa-arrow-left"></i>
                    <span>Ir Atrás</span>
                </a>

                {{-- Los otros botones ahora también usan la clase base para ser proporcionales --}}
                <button id="btnPapel" class="btn-base btn-gradient btn-gradient-secondary">Agregar Inspección en Papel</button>
                <button id="btnGuardar" class="btn-base btn-gradient btn-gradient-success">
                    <span>Guardar</span>
                </button>
            </div>
        </div>
        <div class="card text-center border-info w-100">
           {{-- <div class="card-header" style="white-space: nowrap;">
                <div class="nav-wrapper"
                     style="overflow-x: hidden ; overflow-y: hidden; flex-direction: column-reverse;">

                     <ul class="nav nav-tabs card-header-tabs flex-nowrap">

                         <?php foreach ($nombres as $index => $nombre) : ?>
                             <?php $partes_nombre = explode(' ', $nombre);
                             $nombre_corto = $partes_nombre[0] . ' ' . $partes_nombre[2]; // Primer apellido y primer nombre
                             ?>
                             <li class="nav-item" style="white-space: nowrap;">
                                 <a href="#<?= $nombre; ?>" class="nav-link btnav <?= $index === 0 ? 'active' : ''; ?>" data-bs-toggle="tab"><?= $nombre_corto; ?></a>
                             </li>
                         <?php endforeach; ?>
                     </ul>
                </div>
            </div>--}}
            {{-- <div class="row mt-3">
                 <div class="col">
                     <button class="btn btn-outline-secondary scroll-left">&lt;</button>
                 </div>
                 <div class="col">
                     <button class="btn btn-outline-secondary scroll-right">&gt;</button>
                 </div>
             </div>--}}
            <div class="card-body">

                <div class="nav-wrapper" style="overflow-x: auto; overflow-y: hidden; display: flex;">
                    <div class="tab-content">

                        <?php foreach ($nombres as $index => $nombre) : ?>
                        <div class="col-md-4">
                            <table class="table no-datatable tabla-indicadores"
                                   style="<?= $index === 0 ? 'display: table' : 'display: none'; ?>"
                                   id="#<?= $nombre; ?>">
                                <thead>
                                <tr>
                                    <th style="display: none;"></th>
                                    <th style="display: none;"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>CERTIFICADA</td>
                                    <td class="certificadaCount <?= $nombre ?>">0</td>
                                </tr>
                                <tr>
                                    <td>CERTIFICADA CON NOVEDADES</td>
                                    <td class="certificadaConNovedadesCount <?= $nombre ?>">0</td>
                                </tr>
                                <tr>
                                    <td>INSPECCIONADA CON DEFECTO CRITICO VALLE</td>
                                    <td class="inspeccionadaConDefectoCriticoCount <?= $nombre ?>">0</td>
                                </tr>
                                <tr>
                                    <td>INSPECCIONADA CON DEFECTO NO CRITICO VALLE</td>
                                    <td class="inspeccionadaConDefectoNoCriticoCount <?= $nombre ?>">0</td>
                                </tr>
                                <tr>
                                    <td><b>TOTAL CONTRATOS OK</b></td>
                                    <td class="totalCount <?= $nombre ?>">0</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade show active" id="<?= $nombre; ?>" role="tabpanel">
                            <table class="table table-striped table-bordered tbl_datos"
                                   style="<?= $index === 0 ? 'display: table' : 'display: none'; ?>"
                                   id="#<?= $nombre; ?>">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>INSPECTOR</th>
                                    <th>CC OPERARIO</th>
                                    <th>MUNICIPIO</th>
                                    <th>FECHA</th>
                                    <th>N° ACTA</th>
                                    <th>TIPO DE TRABAJO</th>
                                    <th>CONTRATO</th>
                                    <th>ORDEN TRABAJO</th>
                                    <th>ORDEN EXT</th>
                                    <th>CATEGORIA</th>
                                    <th>RESULTADO CIERRE</th>
                                    <th>HORA INICIO</th>
                                    <th>HORA FINAL</th>
                                    <th>DURACION INSP</th>
                                    <th>4 RECINTOS O MAS</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                                @php
                                    $datosFiltrados = array_filter($response->toArray(), function ($row) use ($cedulas,$index) {
                                    return $row['CC_OPERARIO'] === $cedulas[$index];
                                    });
                                @endphp
                                </thead>
                                <tbody>
                                @foreach ($datosFiltrados as $row)
                                    <tr style='<?php
                                                    echo ($row['vence'] === "60 meses") ? "background-color: rgb(251,201,255);" : "";
                                                    echo ($row['PERIODO_GRACIA'] === 1) ? "background-color: rgb(236, 243, 132);" : "";
                                                    echo ($row['G_DEVOLUCION'] === 1) ? "background-color: rgb(255, 204, 204);" : "";
                                                    ?>'>
                                        @php
                                            $horaInicialObj = null;
                                            $horaFinalObj = null;
                                        @endphp
                                        <td>{{$row['id']}}</td>
                                        <td>{{$row['NOMBRE']}}</td>
                                        <td>{{$row['CC_OPERARIO']}}</td>
                                        <td>{{$row['MUNICIPIO']}}</td>
                                        <td>{{$row['FECHA']}}</td>
                                        <td>{{$row['No_ACTA']}}</td>
                                        <td>{{$row['TIPO_TRABAJO']}}</td>
                                        <td style='background-color: rgb(146, 208, 80);'>{{$row['CONTRATO']}}</td>
                                        <td>{{$row['ORDEN_TRABAJO']}}</td>
                                        <td>{{$row['ORDEN_EXT']}}</td>
                                        @if($row['CATEGORIA'] === 'COMERICAL')
                                            <td style='background-color: rgb(255, 165, 0)'>{{$row['CATEGORIA']}}</td>
                                        @else
                                            <td>{{$row['CATEGORIA']}}</td>
                                        @endif
                                        <td>{{$row['RESULTADO_CIERRE']}}</td>
                                        <td>{{ $row['HORA_INICIO'] ?? '' }}</td>
                                        <td>{{ $row['HORA_FINAL'] ?? '' }}</td>

                                            <?php // Verificar si la hora final es anterior a la hora inicial
                                            if (isset($row['HORA_INICIO']) && isset($row['HORA_FINAL'])) {
                                                $horaInicialObj = DateTime::createFromFormat('H:i', $row['HORA_INICIO']);
                                                $horaFinalObj = DateTime::createFromFormat('H:i', $row['HORA_FINAL']);

                                                if ($horaFinalObj < $horaInicialObj) {
                                                    // Sumar un día a la hora final
                                                    $horaFinalObj->add(new DateInterval('P1D'));
                                                }

                                                // Calcular la diferencia de tiempo
                                                $duracion = $horaInicialObj->diff($horaFinalObj);

                                                // Obtener la duración total en minutos
                                                $duracionTotalMinutos = $duracion->h * 60 + $duracion->i;

                                                // Formatear la duración en formato HH:MM
                                                $duracionFormato = $duracion->format('%H:%I');

                                                // Verificar si la duración es menor o igual a 20 minutos
                                                if ($duracionTotalMinutos <= 20) {
                                                    echo "<td style='background-color: rgb(255, 165, 0)'>$duracionFormato</td>";
                                                } else {
                                                    echo "<td>$duracionFormato</td>";
                                                }
                                            } else {
                                                echo "<td> </td>";
                                            }
                                            ?>
                                        <td>
                                            <input type="checkbox" value="" id="checkRecintos"
                                                   class="recintosCheck" {{ $row['4_RECINTOS'] !== "NO"  ? 'checked' : '' }}>
                                            <input type="text" id="NroRecintos" class="recintos" size="1"
                                                   style="text-align: center;"
                                                   value="{{ $row['4_RECINTOS'] !== "NO"  ? $row['4_RECINTOS'] : '' }}"
                                                   disabled>
                                        </td>
                                        <td>
                                            <select class='form-select nombre-columna' style="width: 80px;">
                                                <option value="OK" {{ $row['ESTADO'] == 'OK' ? 'selected' : '' }}>OK
                                                </option>
                                                <option value="DV" {{ $row['ESTADO'] == 'DV' ? 'selected' : '' }}>DV
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class='combo2' style="width: 220px; display: none;">
                                                @foreach ($causales as $causal)
                                                    <option
                                                        value="{{$causal->nom_causal}}" {{ $row['CAUSAL'] == $causal->nom_causal ? 'selected' : '' }}>
                                                        {{$causal->nom_causal}}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            {{$row['vence']}}
                                        </td>
                                        <td>
                                        </td>
                                        <td>
                                            {{ $row['PERIODO_GRACIA'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="ventanaEmergente" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <i class="fas fa-plus-circle" style="color: #28a745; margin-right: 8px;"></i>
                            Agregar Inspección en Papel
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <div class="form-field-group">
                            <label for="nombre"><i class="fas fa-user-tie"></i> Inspector:</label>
                            <select class="form-control" name="nombre" id="nombre">
                                <option value="">Seleccione Inspector</option>
                                @foreach ($inspectores as $inspector)
                                    <option value="{{$inspector->cedula}}" data-nombres="{{$inspector->apellidos}} {{$inspector->nombres}}">{{$inspector->apellidos}} {{$inspector->nombres}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field-group">
                            <label for="municipio-select"><i class="fas fa-map-marker-alt"></i> Municipio:</label>
                            <select class="form-control" name="municipio" id="municipio-select"></select>
                        </div>

                        <div class="form-field-group">
                            <label for="fecha"><i class="fas fa-calendar-alt"></i> Fecha:</label>
                            <input type="date" class="form-control" name="fecha" id="fecha" placeholder="dd-mm-yy">
                        </div>

                        <div class="form-field-group">
                            <label for="N°acta"><i class="fas fa-file-alt"></i> N° ACTA</label>
                            <input type="text" class="form-control" name="N°acta" id="N°acta">
                        </div>

                        <div class="form-field-group">
                            <label for="tipo_trabajo"><i class="fas fa-tools"></i> Tipo de Trabajo</label>
                            <select class="form-control" name="tipo_trabajo" id="tipo_trabajo">
                                <option value="">Seleccione Tipo de Trabajo</option>
                                <option value="FI-29 revisión periódica línea matriz">FI-29 revisión periódica línea matriz</option>
                                <option value="FI-31 REVISIÓN NUEVA LINEA MATRIZ">FI-31 REVISIÓN NUEVA LINEA MATRIZ</option>
                                <option value="RP 10444">RP 10444</option>
                                <option value="RP 12161">RP 12161</option>
                                <option value="RN 12162">RN 12162</option>
                                <option value="SA 12163">SA 12163</option>
                                <option value="SA 12164">SA 12164</option>
                            </select>
                        </div>

                        <div class="form-field-group">
                            <label for="contrato"><i class="fas fa-handshake"></i> Contrato</label>
                            <input type="text" class="form-control" name="contrato" id="contrato" value=":">
                        </div>

                        <div class="form-field-group">
                            <label for="resultado_cierre"><i class="fas fa-flag-checkered"></i> Resultado Cierre</label>
                            <select class="form-control" name="resultado_cierre" id="resultado_cierre">
                                <option value="">Seleccione Resultado</option>
                                <option value="CERTIFICADA">CERTIFICADA</option>
                                <option value="INSPECCIONADA CON DEFECTO CRITICO VALLE">INSPECCIONADA CON DEFECTO CRITICO VALLE</option>
                                <option value="INSPECCIONADA CON DEFECTO NO CRITICO VALLE">INSPECCIONADA CON DEFECTO NO CRITICO VALLE</option>
                            </select>
                        </div>

                        <div class="form-field-group matriz-des1">
                            <label for="categoria"><i class="fas fa-tag"></i> Categoría</label>
                            <select class="form-control" name="categoria" id="categoria">
                                <option value="">Seleccione Categoría</option>
                                <option value="RESIDENCIAL">RESIDENCIAL</option>
                                <option value="COMERCIAL">COMERCIAL</option>
                            </select>
                        </div>

                        <div class="form-field-group matriz-des2">
                            <label for="recintos"><i class="fas fa-clipboard-list"></i> 4 Recintos o más</label>
                            <select class="form-control" name="recintos" id="recintos">
                                <option value="NO" selected>NO</option>
                                <option value="SI">SI</option>
                            </select>
                        </div>

                        <div class="form-field-group matriz-des2">
                            <label for="NroRecintosP"><i class="fas fa-hashtag"></i> Cantidad de recintos</label>
                            <input type="text" class="form-control" id="NroRecintosP" style="text-align: center;" disabled>
                        </div>

                        <div class="form-field-group causal" style="display: none;">
                            <label for="causal"><i class="fas fa-exclamation-triangle"></i> Causal de rechazo</label>
                            <input type="text" class="form-control" name="causal" id="causal">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button class="btn btn-success" id="agregar">Agregar</button>
                    </div>
                </div>
            </div>
        </div>

      {{--  <div class="d-grid gap-2 d-md-flex justify-content-md">
            <div class="card-footer" style="margin-top: 10px; margin-bottom: 10px;">
                <button class="btn btn-success" id="btnGuardar">Guardar</button>
            </div>
        </div>--}}


    </div>
    @php
        $datos = $response->toArray();
    @endphp
    @section('js')
        <!--  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script> -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
        <script>
            const id_bitacora = "{{ $datos[0]['id_bitacora'] }}";
            const super_id = "{{ $datos[0]['id_super'] }}";

            const causalesData = {!! json_encode($causales) !!};
            $(document).ready(function () {


                $('#ventanaEmergente').on('shown.bs.modal', function () {
                    // Variable para almacenar la instancia de Tom Select y evitar reinicializarla
                    let tomSelectInstance = null;

                    // Obtenemos el elemento del DOM
                    const selectElement = document.querySelector('#municipio-select');

                    // Verificamos si ya tiene una instancia para no duplicarla
                    if (selectElement.tomselect) {
                        // Si ya existe, la limpiamos por si el modal se abre varias veces
                        selectElement.tomselect.clear();
                        selectElement.tomselect.clearOptions();
                    } else {
                        // Si no existe, creamos la instancia
                        tomSelectInstance = new TomSelect(selectElement, {
                            valueField: 'id',      // El campo del JSON que se usará como valor (value)
                            labelField: 'text',    // El campo del JSON que se usará como texto visible
                            searchField: 'text',   // El campo por el cual se puede buscar
                            create: false,         // Evita que el usuario pueda crear nuevas opciones
                            placeholder: 'Escribe 2 caracteres', // Texto de ayuda inicial

                            // Función para cargar los datos desde el servidor
                            load: function(query, callback) {


                                // Hacemos la petición AJAX (usando fetch, una alternativa moderna a $.ajax)
                                fetch(`{{route('municipios.json')}}?term=${encodeURIComponent(query)}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        // Transformamos los datos al formato que Tom Select necesita: un array de objetos
                                        const results = Object.keys(data).map(key => {
                                            return { id: key, text: data[key] };
                                        });
                                        // Devolvemos los resultados
                                        callback(results);
                                    }).catch(() => {
                                    // En caso de error, devolvemos un array vacío
                                    callback();
                                });
                            },

                        });

                    }
                });
             /*   $(window).on('resize', function () {
                    $('#municipio-select').select2('destroy'); // Destruir la instancia actual de Select2
                    $('#municipio-select').select2({
                        language: "es",
                        ajax: {
                            url: "{{'municipios.json'}}", // Ruta a la función del controlador
                            dataType: 'json',
                            delay: 250, // Retraso antes de realizar la búsqueda
                            data: function (params) {
                                return {
                                    term: params.term // Término de búsqueda
                                };
                            },
                            processResults: function (data) {
                                return {
                                    results: $.map(data, function (item, key) { // Mapear resultados
                                        return {
                                            id: key,
                                            text: item
                                        };
                                    })
                                };
                            },
                            cache: true
                        },
                        minimumInputLength: 2 // Mínimo de caracteres para iniciar la búsqueda
                    });

                });*/

                /*const id_super = {!!json_encode($id_super) !!};

                if (id_super == null) {

                    document.getElementById('btnGuardar').click(); // Habilitar el botón

                }*/


            });


        </script>
    @stop
    </body>

@endsection
