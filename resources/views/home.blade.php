@extends('adminlte::page')

@section('content_header')

<h1>Dashboard</h1>
@endsection

@section('content')



@if (session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: "Error",
            text: "{{session('error')}}",
            icon: "error"
        });
    });
</script>
@endif
@if (session('success'))
<script>
     document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                position: "top-end",
                icon: "success",
                title: "{{ session('success') }}",
                showConfirmButton: false,
                toast: true,
                timer: 4000
            });
        });
</script>
@endif
@endsection