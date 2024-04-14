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
            type: "error"
        });
    });
</script>
@endif
@endsection