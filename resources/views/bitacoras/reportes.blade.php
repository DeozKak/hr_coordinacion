@extends('adminlte::page')

@section('title', 'Reportes de Bitácoras')

@section('content_header')
<h1>Reportes de Bitácoras</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/bitacoras/Reportes.css')}}">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="{{asset('js/Reportes.js')}}"></script>

<body>
    <style>
        /* En tu archivo CSS (por ejemplo, Reportes.css) */
        .lista-resultados {
            max-height: 200px;
            /* Ajusta la altura máxima según tus necesidades */
            overflow-y: auto;
            /* Habilita el scroll vertical */
        }

        .lista-resultados ul {
            list-style: none;
            /* Elimina los puntos de la lista */
            padding: 0;
            margin: 0;
        }

        .lista-resultados li {
            padding: 8px;
            cursor: pointer;
            /* Cambia el cursor a una mano para indicar que es seleccionable */
        }

        .lista-resultados li:hover {
            background-color: #f5f5f5;
            /* Cambia el color de fondo al pasar el mouse por encima */
        }
    </style>
    <div class="container">
        <div class="row justify-content-center mt-3 shadow-container">
            <div class="col-md-12">
                <div class="col-md-12 table-responsive" style="padding: 17px;">
                    <div class="form-group">
                        <input type="text" class="form-control" id="buscadorContrato" placeholder="Buscar contrato...">
                    </div>

                    <div id="resultadosBusqueda" class="lista-resultados">
                    </div>

                    <div class="col-md-12 table-responsive" style="padding: 17px;">
                    </div>
                    <table class="table table-striped table-bordered" id="devoluciones">
                        <thead>
                            <tr>
                                <th>Nombre Reporte</th>
                                <th>Usuario</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bitacoras as $bitacora)
                            <tr>
                                <td>{{$bitacora->nombre_archivo}}</td>
                                <td>{{$bitacora->Usuario->name}}</td>
                                <td>{{$bitacora->fecha_creacion}}</td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Botones">
                                        <form action="{{route('bitacoras.ver_reporte',['id_bitacora'=> $bitacora->id])}}" method="GET">

                                            <button class="btn btn-primary" id="verReporte">Ver reporte</button>
                                        </form>
                                        <form action="{{route('bitacoras.download',['file'=>$bitacora->nombre_archivo.".xlsx"])}}" method="GET">
                                            <button class="btn btn-success" id="btnDescargar">Descargar Xlsx</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @section('js')
    <script>
        $(document).ready(function() {
            $('#buscadorContrato').on('input', function() {
                var contrato = $(this).val();
                buscarBitacorasPorContrato(contrato);
            });
        });

        function buscarBitacorasPorContrato(contrato) {
            $.ajax({
                // ... (tu configuración AJAX)
                success: function(response) {
                    console.log(response);
                    let listaHtml = '<ul>';
                    response.forEach(bitacora => {
                        listaHtml += `<li data-id="${bitacora.id}">${bitacora.nombre_archivo} (ID: ${bitacora.id})</li>`;
                    });
                    listaHtml += '</ul>';
                    $('#resultadosBusqueda').html(listaHtml);

                    // Evento de clic para cada elemento de la lista
                    $('.lista-resultados li').click(function() {
                        const idBitacora = $(this).data('id');
                        // Aquí puedes realizar la acción que desees al seleccionar una bitácora
                        // Por ejemplo, mostrar los detalles de la bitácora en otro lugar de la página
                        console.log("Bitácora seleccionada:", idBitacora);
                    });
                }
            });
        }
    </script>
    @stop
</body>

@endsection