@extends('adminlte::page')

@section('title', 'Contratos sin categoria')

@section('content_header')
    <h1>Contratos sin categoria</h1>
@stop

@section('content')
    <style>
        #message {
            font-size: 1.15rem;
            display: none;
            transition: opacity .5s;
            opacity: 0;
        }

        #message.show {
            display: block !important;
            opacity: 1;
        }
    </style>
    <input type="hidden" id="url" value="{{ route('bitacoras.contratos_sin_categoria.StoreCategoria') }}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <div class="container-fluid py-3">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-9 px-2">
                <div class="card shadow rounded-4 border-0" style="min-width: 900px;">
                    <div class="card-body p-4">
                        <div class="alert alert-info text-center mb-3" id="message">
                            <div class="d-flex flex-column align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="#0dcaf0" class="mb-2" viewBox="0 0 16 16">
                                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm0-1A7 7 0 1 1 8 1a7 7 0 0 1 0 14z"/>
                                    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 .877-.252 1.002-.797l.088-.416c.066-.3.126-.424.416-.471l.451-.083.082-.38-2.29-.287-.082-.38.45-.083a.513.513 0 0 0 .288-.469l.738-3.468c.194-.897-.105-1.319-.808-1.319-.545 0-.877.252-1.002.797l-.088.416c-.066.3-.126.424-.416.471z"/>
                                    <circle cx="8" cy="4.5" r="1"/>
                                </svg>
                                <span>Nada que mostrar, todos los contratos tienen categoría.</span>
                            </div>
                        </div>
                        <div id="table"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="{{ asset('js/bitacora/categoria.js') }}"></script>
        <script>

            let registros = {!! $contratos_sin_categoria->toJson() !!};
            console.log(registros);

            let nuevoArray = registros.map(registro => ({
                id: registro.id,
                CC_OPERARIO: registro.CC_OPERARIO,
                MUNICIPIO: registro.MUNICIPIO,
                FECHA: registro.FECHA,
                No_ACTA: registro.No_ACTA,
                TIPO_TRABAJO: registro.TIPO_TRABAJO,
                CONTRATO: registro.CONTRATO,
                ORDEN_TRABAJO: registro.ORDEN_TRABAJO,
                ORDEN_EXT: registro.ORDEN_EXT,
                CATEGORIA: registro.CATEGORIA,
                RESULTADO_CIERRE: registro.RESULTADO_CIERRE,
                /* HORA_INICIO: registro.HORA_INICIO,
                 HORA_FINAL: registro.HORA_FINAL,*/
            }));

        </script>
@endsection




