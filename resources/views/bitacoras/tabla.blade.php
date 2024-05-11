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
    <script src="{{asset('js/tbl_dinamica.js')}}"></script>

</head>

<body>

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
                                            <td>.CERTIFICADA</td>
                                            <td class="certificadaCount <?= $nombre ?>">0</td>
                                        </tr>
                                        <tr>
                                            <td>CERTIFICADA CON NOVEDADES</td>
                                            <td class="certificadaConNovedadesCount <?= $nombre ?>">0</td>
                                        </tr>
                                        <tr>
                                            <td>.INSPECCIONADA CON DEFECTO CRITICO VALLE</td>
                                            <td class="inspeccionadaConDefectoCriticoCount <?= $nombre ?>">0</td>
                                        </tr>
                                        <tr>
                                            <td>.INSPECCIONADA CON DEFECTO NO CRITICO VALLE</td>
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
                                            <?php foreach ($spreadsheet->getSheetNames() as $sheetName) : ?>
                                                <?php
                                                $sheet = $spreadsheet->getSheetByName($sheetName);
                                                ?>
                                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'M', 'N', 'O'] as $columna) : ?>
                                                    <?php if ($columna === 'I') {
                                                        echo "<th>ORDEN TRABAJO</th>";
                                                    } elseif ($columna === 'J') {
                                                        echo "<th>ORDEN EXT</th>";
                                                    } elseif ($columna === 'M') {
                                                        echo "<th>RESULTADO CIERRE</th>";
                                                    } else {
                                                        echo "<th>" . $sheet->getCell($columna . '1')->getValue() . "</th>";
                                                    }   ?>

                                                <?php endforeach; ?>
                                                <?php break; // Rompe el bucle después de la primera iteración 
                                                ?>
                                            <?php endforeach; ?>
                                            <th>DURACION INSP</th>
                                            <th>4 RECINTOS O MAS</th>
                                            <th></th>
                                            <th></th>
                                        </tr>

                                    </thead>
                                    <tbody>
                                        <?php foreach ($spreadsheet->getSheetNames() as $sheetName) : ?>
                                            <?php
                                            $sheet = $spreadsheet->getSheetByName($sheetName);
                                            ?>
                                            <?php foreach ($sheet->getRowIterator() as $row) : ?>
                                                <?php
                                                $contrato = $sheet->getCell('H' . $row->getRowIndex())->getValue();
                                                $nombreCelda = $sheet->getCell('A' . $row->getRowIndex())->getValue();
                                                $Cierre = $sheet->getCell('M' . $row->getRowIndex())->getValue();
                                                ?>
                                                <?php if (strpos($contrato,":") === 0 && $nombreCelda === $nombre && ($Cierre === ".CERTIFICADA" || $Cierre === "CERTIFICADA CON NOVEDADES" || $Cierre === ".INSPECCIONADA CON DEFECTO CRITICO VALLE" || $Cierre === ".INSPECCIONADA CON DEFECTO NO CRITICO VALLE")) : ?>
                                                    <tr>
                                                        <?php $horaInicialObj = null;
                                                        $horaFinalObj = null;
                                                        foreach (['A', 'B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'M', 'N', 'O'] as $columna) : ?>
                                                            <?php
                                                            $valorCelda = $sheet->getCell($columna . $row->getRowIndex())->getValue();
                                                            if ($columna === 'H') {

                                                                echo "<td style='background-color: rgb(146, 208, 80);'>" . $valorCelda . "</td>";
                                                               
                                                            } elseif ($columna === 'D') {
                                                                $fechaNumeroNatural = $valorCelda;
                                                                $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaNumeroNatural);
                                                                $valorCelda = $fecha->format('d-m-y');
                                                                echo "<td>" . $valorCelda . "</td>";
                                                            } elseif ($columna === 'K') {
                                                                $categoria = $sheet->getCell('K' . $row->getRowIndex())->getValue();
                                                                if ($categoria === "COMERCIAL") {
                                                                    echo "<td style='background-color: rgb(255, 165, 0)'>" . $categoria . "</td>";
                                                                } else {
                                                                    echo "<td>" . $categoria . "</td>";
                                                                }
                                                            } else {
                                                                echo "<td>" . $valorCelda . "</td>";
                                                            }

                                                            if ($columna === 'N') {
                                                                $horaInicial = $sheet->getCell('N' . $row->getRowIndex())->getValue();
                                                                $horaInicialObj  = DateTime::createFromFormat('H:i', $horaInicial);
                                                            } elseif ($columna === 'O') {
                                                                $horaFinal = $sheet->getCell('O' . $row->getRowIndex())->getValue();
                                                                $horaFinalObj  = DateTime::createFromFormat('H:i', $horaFinal);
                                                            }
                                                            ?>
                                                        <?php endforeach; ?>

                                                        <?php // Verificar si la hora final es anterior a la hora inicial
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
                                                        <td><input type="checkbox" value=""></td>
                                                        <td>
                                                            <select class='form-select nombre-columna' style="width: 80px;">
                                                                <option value="OK" selected>OK</option>
                                                                <option value="DV">DV</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select class='form-select combo2 nombre-columna' style="width: 220px; display: none;">
                                                                <option value="--SELECCIONE CAUSAL--" selected>--SELECCIONE CAUSAL--</option>
                                                                <option value="CONTRATO ERRADO">CONTRATO ERRADO</option>
                                                                <option value="NUMERO DE CUOTAS">NUMERO DE CUOTAS</option>
                                                                <option value="FALTA CARTA">FALTA CARTA</option>
                                                                <option value="FALTA INFORMACIÓN">FALTA INFORMACIÓN</option>
                                                                <option value="INFORMACION ERRADA">INFORMACION ERRADA</option>
                                                                <option value="ORDEN YA REGISTRADA">ORDEN YA REGISTRADA</option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
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
                                <select class="form-control" name="municipio" id="municipio">
                                    <option value="">Seleccione Municipio</option>
                                    @foreach ($municipios as $municipio)
                                    <option value="{{$municipio->nombre}}">{{$municipio->nombre}}</option>
                                    @endforeach
                                </select>
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
                                <label for="orden_trabajo">Orden de trabajo</label>
                                <input type="text" class="form-control" name="orden_trabajo" id="orden_trabajo">
                            </div>

                            <br>

                            <div class="col-md-6">
                                <label for="categoria">Categoria</label>
                                <select class="form-control" name="categoria" id="categoria">
                                    <option value="">Seleccione categoria</option>
                                    <option value="RESIDENCIAL">RESIDENCIAL</option>
                                    <option value="COMERCIAL">COMERCIAL</option>
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <div class="col-md-6">
                                <label for="hora_inicio">Hora Inicio</label>
                                <input type="time" class="form-control" name="hora_inicio" id="hora_inicio" step="60" pattern="[0-9]{2}:[0-9]{2}">
                            </div>
                            <div class="col-md-6">
                                <label for="hora_final" style="margin-left: 10px;">Hora Final</label>
                                <input type="time" class="form-control" name="hora_final" id="hora_final" step="60" pattern="[0-9]{2}:[0-9]{2}">
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <div class="col-md-6">
                                <label for="recintos">4 recintos o más</label>
                                <select class="form-control" name="recintos" id="recintos">
                                    <option value="NO" selected>NO</option>
                                    <option value="SI">SI</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="devolucion">Estado</label>
                                <select class="form-control" name="devolucion" id="devolucion">
                                    <option value="OK" selected>OK</option>
                                    <option value="DV">DV</option>
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <div class="col-md-6">
                                <label for="resultado_cierre">Resultado Cierre</label>
                                <select class="form-control" name="resultado_cierre" id="resultado_cierre">
                                    <option value="">Seleccione categoria</option>
                                    <option value=".CERTIFICADA">.CERTIFICADA</option>
                                    <option value="CERTIFICADA CON NOVEDADES">CERTIFICADA CON NOVEDADES</option>
                                    <option value=".INSPECCIONADA CON DEFECTO CRITICO VALLE">.INSPECCIONADA CON DEFECTO CRITICO VALLE</option>
                                    <option value=".INSPECCIONADA CON DEFECTO NO CRITICO VALLE">.INSPECCIONADA CON DEFECTO NO CRITICO VALLE</option>
                                </select>
                            </div>
                            <div class="col-md-6" style="display: none;" id="causal_devolucion">
                                <label for="causal">Causal Devolución</label>
                                <select class="form-control" name="causal" id="causal">
                                    <option value="--SELECCIONE CAUSAL--" selected>--SELECCIONE CAUSAL--</option>
                                    <option value="CONTRATO ERRADO">CONTRATO ERRADO</option>
                                    <option value="NUMERO DE CUOTAS">NUMERO DE CUOTAS</option>
                                    <option value="FALTA CARTA">FALTA CARTA</option>
                                    <option value="FALTA INFORMACIÓN">FALTA INFORMACIÓN</option>
                                    <option value="INFORMACION ERRADA">INFORMACION ERRADA</option>
                                    <option value="ORDEN YA REGISTRADA">ORDEN YA REGISTRADA</option>
                                </select>
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
                <a class="btn btn-primary" href="javascript:history.go(-1)">Ir Atrás</a>
                <button class="btn btn-success" id="btnGuardar">Guardar</button>
            </div>
        </div>


    </div>

</body>



@endsection