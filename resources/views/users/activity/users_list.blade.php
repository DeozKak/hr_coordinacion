@extends('adminlte::page')

@section('title', 'Actividad Usuarios')

@section('content_header')
    <h1>Actividad</h1>
@endsection

@section('content')
    <style>
        .shadow-container {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            padding: 20px;
            max-width: 500px;
            margin: 0 auto;
            border-radius: 10px;
            background-color: #f8f9fa;
        }
    </style>
    <div class="container">
        <div class="card">
            <div class="card-body">
                <h1>Lista de Usuarios</h1>
                <table class="table" id="usersTable"> {{-- Añadido id="usersTable" --}}
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <a href="{{ route('admin.user.activity.show', $user) }}" class="btn btn-sm btn-info">Ver
                                    Actividad</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No se encontraron usuarios.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                {{-- {{ $users->links() }} --}} {{-- Eliminada la paginación de Laravel --}}
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>

        $('#usersTable').DataTable({
            // Opciones de configuración de DataTables (opcional)
            "language": { // Ejemplo de traducción al español
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
            },
            // "paging": true,
            // "searching": true,
            // "ordering": true,
            // ... más opciones
        });

    </script>
@endsection
