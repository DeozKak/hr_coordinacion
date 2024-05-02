@extends('adminlte::page')

@section('title', 'Bitácoras')

@section('content_header')
    <h1>Bitácoras</h1>
@endsection

@section('content')

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="{{asset('css/bitacoras/generar.css')}}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <title>Subir Archivos</title>
</head>

<body class="body">
  
    <div class="container mt-5">
        <div class="shadow-container">
            <h2 class="text-center mb-4">Subir Archivos</h2>
           @role('Supervisor')
            <form action="routes.php?accion=Generar_Bitacora" method="POST" enctype="multipart/form-data">
                <select class="form-select form-select-lg mb-3" name="supervisor" id="supervisor" required disabled>                                     
                <option value="{{$supervisores->id}}"  selected>{{$supervisores->name}}</option>                
                </select>
                <label for="archivo" class="form-label">Seleccione Bitacora:</label>
                @csrf
                <input class="form-control mb-3" type="file" name="archivo" id="archivo" required>
                
                <div class="button-container">
                    <button class="btn btn-primary" type="submit">Subir Archivo</button>
                
            </form>
            @endrole
            @unlessrole('Supervisor')
            <form action="routes.php?accion=Generar_Bitacora" method="POST" enctype="multipart/form-data">
                <select class="form-select form-select-lg mb-3" name="supervisor" id="supervisor" required>
                    <option value="">Seleccione Supervisor</option>
                    @foreach ($supervisores as $supervisor)
                        
                    <option value="{{$supervisor->id}}" >{{$supervisor->name}}</option>
                    @endforeach
                </select>
                <label for="archivo" class="form-label">Seleccione Bitacora:</label>
                @csrf
                <input class="form-control mb-3" type="file" name="archivo" id="archivo" required>
                
                <div class="button-container">
                    <button class="btn btn-primary" type="submit">Subir Archivo</button>
                
            </form>
            @endunlessrole
            <form action="routes.php?accion=devoluciones" method="POST" enctype="multipart/form-data">
                    <button class="btn btn-primary" type="submit">Ver contratos en Devolución</button>
                </div>
            </form>
        </div>
    </div>

    <script>
       document.addEventListener('DOMContentLoaded',(event) =>{
            if(<?php echo isset($_SESSION['mostrarAlertaError']) ? 'true' : 'false'; ?>){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php if(isset($_SESSION['mostrarAlertaError'])){echo  $_SESSION['mostrarAlertaError'];} ?>',
            });
            <?php if(isset($_SESSION['mostrarAlertaError'])){unset($_SESSION['mostrarAlertaError']);} ?>
        }
        });
    </script>
</body>

</html>

@endsection