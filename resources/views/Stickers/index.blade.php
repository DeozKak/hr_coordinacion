@extends('adminlte::page')

@section('title', 'Stickers')

@section('content_header')
    <h1>Control Stickers</h1>
@stop   

@section('content')

<style>
.card {
 padding: 20px; /* Añade un poco de espacio interno */
}
</style>



<div class="container">
    <div class="card">
        <div class="row justify-content-center mt-3 shadow-container">
            <div class="col-md-12">
           
                Semanas
          
                <table class="table table-bordered table-striped" id="semanas">
                    <thead>
                        <tr>
                            <th>Mes / Año</th>
                            <th>Rango</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($semana as $sem )
                            <tr>
                             
                            
                                <td>{{$sem->mes_año}}</td>
                                <td>{{$sem->fecha_inicio}}  a  {{$sem->fecha_fin}}</td>
                                <td>
                                    <form action="{{ route('bitacora.stickers.show', $sem->id) }}" method="GET">
                                        <button type="submit" class="btn btn-primary">Ver</button>
                                    </form>
                                </td>
                              
                            </tr>
                            @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</div>



@section('js')
<script src="{{ asset('js/Stickers/index.js') }}?v={{ time() }}"></script>
@stop
@stop



