@extends('adminlte::page')

@section('title', 'Bitácoras')

@section('content_header')
<h1>Bitácoras</h1>
@endsection

@section('content')

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="{{asset('css/bitacoras/Tablas.css')}}">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset('js/tbl_dinamicaV3.js')}}"></script>
</head>

<body>
    <div id="miContenedor" data-id-super="{{ json_encode($id_super) }}"></div>
    <div id="loader" style="display: none;"></div>
    <div id="overlay" style="display: none;"></div>
    <input type="hidden" id="token" name="csrf_token" value="{{ csrf_token() }}">
    <input type="hidden" id="url_guardar" name="route_guardar" value="{{ route('bitacoras.guardar_tabla',['super' => $id_super]) }}">
    <input type="hidden" id="url_borrar" name="route_borrar" value="{{ route('bitacoras.borrar_archivos') }}">
    <div class="shadow-container">
        <button class="btn btn-primary" id="btnPapel">Agregar Inspeccion en Papel</button>
        <div class="card text-center border-info w-100">
            <div class="card-header" style="white-space: nowrap;">
                <div class="nav-wrapper" style="overflow-x: hidden ; overflow-y: hidden; flex-direction: column-reverse;">
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
            </div>
            <div class="row mt-3">
                <div class="col">
                    <button class="btn btn-outline-secondary scroll-left">&lt;</button>
                </div>
                <div class="col">
                    <button class="btn btn-outline-secondary scroll-right">&gt;</button>
                </div>
            </div>
            <div class="card-body">

                <div class="nav-wrapper" style="overflow-x: auto; overflow-y: hidden; display: flex;">
                    <div class="tab-content">
                        <?php foreach ($nombres as $index => $nombre) : ?>
                            <div class="col-md-4">
                                <table class="table table-bordered table-sm no-datatable tabla-indicadores" style="<?= $index === 0 ? 'display: table' : 'display: none'; ?>" id="#<?= $nombre; ?>">
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
                                <table class="table table-striped table-bordered tbl_datos" style="<?= $index === 0 ? 'display: table' : 'display: none'; ?>" id="#<?= $nombre; ?>">
                                    <thead>
                                        <tr>
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
                                        </tr>
                                        @php
                                        $datosFiltrados = array_filter($response->toArray(), function ($row) use ($nombre) {
                                        return $row['NOMBRE'] === $nombre;
                                        });
                                     
                                        @endphp
                                    </thead>

                                    <tbody>
                                        @foreach ($datosFiltrados as $row)
                                        @php
                                        $vence = $row['vence'];
                                        $venceDate = DateTime::createFromFormat('d/m/Y', $vence);
                                        if ($venceDate && $venceDate->format('Y') == date('Y') && $venceDate->format('m') == date('m')) {
                                        $vence = "60 meses";
                                        
                                        } else {
                                        $vence = "";
                                        }
                                       
                                        @endphp
                                        <tr style='<?php
                                                    echo ($venceDate && $venceDate->format('Y') == date('Y') && $venceDate->format('m') == date('m')) ? "background-color: rgb(251,201,255);" : "";
                                                    ?>'>
                                            @php
                                            $horaInicialObj = null;
                                            $horaFinalObj = null;
                                            @endphp

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
                                            <td>{{$row['HORA_INICIO']}}</td>
                                            <td>{{$row['HORA_FINAL']}}</td>

                                            <?php // Verificar si la hora final es anterior a la hora inicial
                                            $horaInicialObj  = DateTime::createFromFormat('H:i', $row['HORA_INICIO']);
                                            $horaFinalObj    = DateTime::createFromFormat('H:i', $row['HORA_FINAL']);

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
                                            ?>
                                            <td><input type="checkbox" value="" id="checkRecintos">
                                                <input type="text" id="NroRecintos" size="1" style="text-align: center;" disabled>
                                            </td>
                                            <td>
                                                <select class='form-select nombre-columna' style="width: 80px;">
                                                    <option value="OK" selected>OK</option>
                                                    <option value="DV">DV</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select class='form-select combo2 nombre-columna' style="width: 220px; display: none;">
                                                    @foreach ($causales as $causal )
                                                    <option value="{{$causal->nom_causal}}">{{$causal->nom_causal}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                {{$row['vence']}}
                                            </td>
                                            <td>
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

        <!-- Ventana emergente -->
        <div class="modal fade" id="ventanaEmergente" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Agregar Inspección en papel</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <div class="col-md-6">
                                <label for="nombre">Inspector:</label>
                                <select class="form-control" name="nombre" id="nombre">
                                    <option value="">Seleccione Inspector</option>
                                    @foreach ($inspectores as $inspector)
                                    <option value="{{$inspector->cedula}}" data-nombres="{{$inspector->apellidos}} {{$inspector->nombres}}">{{$inspector->apellidos}} {{$inspector->nombres}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <br>
                            <div class="col-md-6">
                                <label for="municipio">Municipio:</label>
                                <select class="form-control select2" name="municipio" id="municipio-select"></select>
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <div class="col-md-6">
                                <label for="fecha">Fecha:</label>
                                <input type="date" class="form-control" name="fecha" id="fecha" placeholder="dd-mm-yy">
                            </div>

                            <br>

                            <div class="col-md-6">
                                <label for="N°acta">N° ACTA</label>
                                <input type="text" class="form-control" name="N°acta" id="N°acta">
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <div class="col-md-6">
                                <label for="tipo_trabajo">Tipo de Trabajo</label>
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

                            <br>

                            <div class="col-md-6">
                                <label for="contrato">Contrato</label>
                                <input type="text" class="form-control" name="contrato" id="contrato" value=":">
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <div class="col-md-6">
                                <label for="resultado_cierre">Resultado Cierre</label>
                                <select class="form-control" name="resultado_cierre" id="resultado_cierre">
                                    <option value="">Seleccione categoria</option>
                                    <option value="CERTIFICADA">CERTIFICADA</option>
                                    <option value="INSPECCIONADA CON DEFECTO CRITICO VALLE">INSPECCIONADA CON DEFECTO CRITICO VALLE</option>
                                    <option value="INSPECCIONADA CON DEFECTO NO CRITICO VALLE">INSPECCIONADA CON DEFECTO NO CRITICO VALLE</option>
                                </select>
                            </div>
                            <br>
                            <div class="col-md-6 matriz-des1">
                                <label for="categoria">Categoria</label>
                                <select class="form-control" name="categoria" id="categoria">
                                    <option value="">Seleccione categoria</option>
                                    <option value="RESIDENCIAL">RESIDENCIAL</option>
                                    <option value="COMERCIAL">COMERCIAL</option>
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="form-group matriz-des2">
                            <div class="col-md-6">
                                <label for="recintos">4 Recintos o mas</label>
                                <select class="form-control" name="recintos" id="recintos">
                                    <option value="NO" selected>NO</option>
                                    <option value="SI">SI</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="cantidad_recintos">Cantidad de recintos</label>
                                <input type="text" class="form-control" id="NroRecintosP" style="text-align: center;" disabled>
                            </div>
                        </div>
                        <br>
                        <div class="form-group causal" style="display: none;">
                            <div class="col-md-6">
                                <label for="causal">Causal de rechazo</label>
                                <input type="text" class="form-control" name="causal" id="causal">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">

                        <button class="btn btn-success" id="agregar">Agregar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md">
            <div class="card-footer" style="margin-top: 10px; margin-bottom: 10px;">
                <a class="btn btn-primary" href="{{route('bitacora')}}">Ir Atrás</a>
                <button class="btn btn-success" id="btnGuardar">Guardar</button>
            </div>
        </div>


    </div>
    @section('js')
    <!--  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script> -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
    <script>
        $(document).ready(function() {
            $('#ventanaEmergente').on('shown.bs.modal', function() {
                select2();

                function select2() {
                    $('#municipio-select').select2({
                        language: "es",
                        ajax: {
                            url: "{{route('municipios.json')}}", // Ruta a la función del controlador
                            dataType: 'json',
                            delay: 250, // Retraso antes de realizar la búsqueda
                            data: function(params) {
                                return {
                                    term: params.term // Término de búsqueda
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: $.map(data, function(item, key) { // Mapear resultados
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
                }
            });
            $(window).on('resize', function() {
                $('#municipio-select').select2('destroy'); // Destruir la instancia actual de Select2
                $('#municipio-select').select2({
                    language: "es",
                    ajax: {
                        url: "{{'municipios.json'}}", // Ruta a la función del controlador
                        dataType: 'json',
                        delay: 250, // Retraso antes de realizar la búsqueda
                        data: function(params) {
                            return {
                                term: params.term // Término de búsqueda
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: $.map(data, function(item, key) { // Mapear resultados
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

            });

            const id_super = {!!json_encode($id_super) !!};

            if (id_super == null) {

                document.getElementById('btnGuardar').click(); // Habilitar el botón

            }


        });
    </script>
    @stop
</body>



@endsection