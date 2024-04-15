@extends('adminlte::auth.login')
@section('content')

@section('js')
@if (session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: "Advertencia",
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