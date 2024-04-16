@extends('adminlte::auth.login')



@section('auth_body')




<form action="{{route('login')}}" method="post" autocomplete="off">
    @csrf

    <div class="input-group mb-3">
        <input type="email" name="email" class="form-control " value="" placeholder="Email" autofocus="">

        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-envelope "></span>
            </div>
        </div>

    </div>


    <div class="input-group mb-3">
        <input type="password" name="password" class="form-control " placeholder="Contraseña">

        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-lock "></span>
            </div>
        </div>

    </div>


    <div class="row">
        <div class="col-7">
            <div class="icheck-primary" title="Mantenerme autenticado indefinidamente o hasta cerrar la sesión manualmente">
                <input type="checkbox" name="remember" id="remember">

                <label for="remember">
                    Recordarme
                </label>
            </div>
        </div>

        <div class="col-5">
            <button type="submit" class="btn btn-block btn-flat btn-primary">
                <span class="fas fa-sign-in-alt"></span>
                Acceder
            </button>
        </div>
    </div>

</form>






@section('js')
@if (session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: "",
            text: "{{session('error')}}",
            type: "warning"
        });
    });
</script>
@endif

@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: "!Listo!",
            text: "{{session('success')}}",
            type: "success"
        });
    });
</script>
@endif
@endsection
@endsection