@extends('adminlte::page')

@section('title', 'Bitácoras')

@section('content_header')
    <h1>Bitácoras</h1>
@endsection

@section('content')
@dd("fdsd")
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="{{asset('css/bitacoras/Tablas.css')}}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="{{asset('js/tbl_dinamica.js')}}"></script>
</head>
<body class="body">
    <input type="hidden" id="token" name="csrf_token" value="<?php echo (htmlspecialchars($_SESSION['csrf_token'])) ?>">
    <div id="loader" style="display: none;"></div>
    <div id="overlay" style="display: none;"></div>
    <div class="shadow-container">
        <div class="row justify-content-center mt-3">
            <div class="col-lg-12">
                <div class="card text-center border-info w-100">
                    <div class="card-header" style="white-space: nowrap;">
                        <div class="nav-wrapper" style="overflow-x: hidden ; overflow-y: hidden; display: flex; flex-direction: column-reverse;">
                            <ul class="nav nav-tabs card-header-tabs flex-nowrap">
                                <?php foreach ($nombres as $index => $nombre) : ?>
                                    <?php $partes_nombre = explode(' ', $nombre);
                                    $nombre_corto = $partes_nombre[0] . ' ' . $partes_nombre[2]; // Primer apellido y primer nombre
                                    ?>
                                    <li class="nav-item" style="white-space: nowrap;">
                                        <a href="#<?= $nombre; ?>" class="nav-link <?= $index === 0 ? 'active' : ''; ?>" data-bs-toggle="tab"><?= $nombre_corto; ?></a>
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
                                                        $nombreCelda = $sheet->getCell('A' . $row->getRowIndex())->getValue();
                                                        $Cierre = $sheet->getCell('M' . $row->getRowIndex())->getValue();
                                                        ?>
                                                        <?php if ($nombreCelda === $nombre && ($Cierre === ".CERTIFICADA" || $Cierre === "CERTIFICADA CON NOVEDADES" || $Cierre === ".INSPECCIONADA CON DEFECTO CRITICO VALLE" || $Cierre === ".INSPECCIONADA CON DEFECTO NO CRITICO VALLE")) : ?>
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
                <div class="d-grid gap-2 d-md-flex justify-content-md">
                    <div class="card-footer" style="margin-top: 10px; margin-bottom: 10px;">
                        <div class="card-footer" style="margin-top: 10px; margin-bottom: 10px;">
                            <a class="btn btn-primary" href="javascript:history.go(-1)">Ir Atrás</a>
                            <button class="btn btn-success" id="btnGuardar">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>



@endsection