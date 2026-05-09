<!DOCTYPE html>
<html>
<head>
    <title>Reporte Supervisor</title>
</head>
<body>
<style>
    .table-excel {
        border-collapse: collapse;
        width: 100%;
    }
    .table-excel th {
        background-color: #4F81BD;
        color: #FFFFFF;
        font-weight: bold;
        border: 1px solid #888;
        padding: 6px 4px;
        text-align: center;
    }
    .table-excel td {
        border: 1px solid #888;
        padding: 5px 4px;
        background-color: #F2F2F2;
    }
    .table-excel tr:nth-child(even) td {
        background-color: #DFEFFF;
    }
</style>

<h2>Supervisor: {{ $supervisor->nombre }}</h2>
<table class="table-excel" border="1" width="100%">
    <thead>
    <tr>
        <th>Contrato</th>
        <th>Tipo de trabajo</th>
        <th>Fecha</th>
        <th>Celular</th>
        <th>Nombre de Usuario</th>
        <th>Orden de trabajo</th>
        <th>Direccion</th>
        <th>Barrio</th>
        <th>Ciudad</th>
        <th>Activa</th>
        <th>Suspendido</th>
        <th>Categoria</th>
        <th>Fecha Agendamiento</th>
        <th>Observacion</th>
        <th>Quien programo</th>
        <th>Tecnico</th>
        <th>Jornada</th>
        <!-- ... -->
    </tr>
    </thead>
    <tbody>
    @foreach($registros as $registro)
        <tr>
            <td>{{ $registro[1] }}</td>
            <td>{{ $registro[2] }}</td>
            <td>{{ $registro[3] }}</td>
            <td>{{ $registro[4] }}</td>
            <td>{{ $registro[5] }}</td>
            <td>{{ $registro[6] }}</td>
            <td>{{ $registro[7] }}</td>
            <td>{{ $registro[8] }}</td>
            <td>{{ $registro[9] }}</td>
            <td>{{ $registro[10] }}</td>
            <td>{{ $registro[11] }}</td>
            <td>{{ $registro[12] }}</td>
            <td>{{ $registro[13] }}</td>
            <td>{{ $registro[14] }}</td>
            <td>{{ $registro[15] }}</td>
            <td>{{ $registro[16] }}</td>
            <td>{{ $registro[17] }}</td>

            <!-- ... -->
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
