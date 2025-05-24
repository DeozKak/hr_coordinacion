@extends('adminlte::page')

@section('title', 'Actividad HTTP Usuario')

@section('content_header')
   <h1></h1>
@endsection

@section('styles') {{-- Mover los links CSS aquí para mejor organización si tu layout usa @stack('styles') --}}

<style>
    .dataTables_wrapper {
        width: 100%;
    }
    #spatieActivityLogTable th,
    #spatieActivityLogTable td {
        white-space: nowrap;
        vertical-align: top;
    }
    #spatieActivityLogTable td.properties-cell ul {
        margin: 0;
        padding-left: 15px;
        list-style-position: outside;
        white-space: normal;
    }
    #spatieActivityLogTable td.properties-cell ul li {
        margin-bottom: 3px;
        display: flex;
        align-items: baseline;
    }
    #spatieActivityLogTable td.properties-cell .snippet-text-container {
        display: inline;
    }
    #spatieActivityLogTable td.properties-cell .snippet-text {
        max-width: 350px; /* Ajusta según necesites */
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
        vertical-align: baseline;
    }
    #spatieActivityLogTable td.properties-cell .view-full-json {
        margin-left: 8px;
        white-space: nowrap;
        flex-shrink: 0;
    }
    #jsonModalContent { /* Estilo para el contenido del modal */
        white-space: pre-wrap;
        word-break: break-all;
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        max-height: 70vh;
        overflow-y: auto;
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
        <h2 style="margin-bottom: 10px">Solicitudes Registradas para: {{ $user->name }} ({{ $user->email }})</h2>

        <form method="GET" action="{{ route('admin.user.http_activity.show', $user) }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <label for="log_name_filter">Tipo de Log:</label>
                    <select name="log_name_filter" id="log_name_filter" class="form-control form-select">
                        <option value="">Todos</option>
                        @foreach($available_log_names as $log_name_option)
                            <option
                                value="{{ $log_name_option }}" {{ request('log_name_filter') == $log_name_option ? 'selected' : '' }}>
                                {{ ucfirst($log_name_option) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from">Desde:</label>
                    <input type="date" name="date_from" id="date_from" class="form-control"
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label for="date_to">Hasta:</label>
                    <input type="date" name="date_to" id="date_to" class="form-control"
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 align-self-end">
                    <button type="submit" class="btn btn-primary mt-4">Filtrar</button>
                    <a href="{{ route('admin.user.http_activity.show', $user) }}" class="btn btn-secondary mt-4">Limpiar</a>
                </div>
            </div>
        </form>

        @if($activities->isEmpty() && (request()->has('log_name_filter') || request()->has('date_from') || request()->has('date_to')))
            <p>No hay actividad registrada para este usuario con los filtros actuales.</p>
        @elseif($activities->isEmpty())
            <p>No hay actividad registrada para este usuario.</p>
        @else
            <table class="table table-striped table-hover display" id="spatieActivityLogTable" style="width:100%">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo Log</th>
                    <th>Descripción</th>
                    <th>Propiedades</th>
                    <th>Fecha y Hora</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($activities as $activity)
                    @php
                        $propertiesSnippet = 'N/A';
                        $propertiesFullJson = null;
                        $originalTextForComparison = '';

                        if ($activity->properties && $activity->properties->isNotEmpty()) {
                            $propertiesCollection = $activity->properties; // Es una colección
                            $fullValue = $propertiesCollection->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            // Para el snippet, usamos un JSON compacto y luego lo limitamos
                            $textForSnippet = $propertiesCollection->toJson();
                            $textForSnippet = preg_replace('/\s+/', ' ', $textForSnippet); // Colapsar múltiples espacios
                            $propertiesSnippet = Str::limit($textForSnippet, 150); // Ajusta el límite según necesites

                            $propertiesFullJson = $fullValue;
                            $originalTextForComparison = $textForSnippet;
                        }
                    @endphp
                    <tr>
                        <td>{{ $activity->id }}</td>
                        <td><span class="badge bg-secondary">{{ $activity->log_name }}</span></td>
                        <td>{{ $activity->description }}</td>

                        <td class="properties-cell">
                            <div class="snippet-text-container">
                                <span class="snippet-text" title="{{ $originalTextForComparison }}">{{ $propertiesSnippet }}</span>
                            </div>
                            @if ($propertiesFullJson && (strlen($originalTextForComparison) > strlen(str_replace('...', '', $propertiesSnippet))))
                                <a href="#" class="view-full-json small"
                                   data-json-key="Propiedades (Log ID: {{ $activity->id }})"
                                   data-json-content="{{ htmlspecialchars($propertiesFullJson) }}">
                                    (ver más)
                                </a>
                            @elseif ($propertiesSnippet !== 'N/A' && !$propertiesFullJson)
                                {{-- No se necesita "ver más" si no es un JSON complejo o largo --}}
                            @elseif ($propertiesSnippet === 'N/A')
                                N/A
                            @endif
                        </td>
                        <td>{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
         <div class="mt-3">
            <a href="{{ route('admin.users.activity.list') }}" class="btn btn-secondary">Volver a la lista de
                usuarios</a>
        </div>
    </div>

    {{-- Modal para ver JSON completo (estructura HTML sin cambios) --}}
    <div class="modal fade" id="jsonDetailModal" tabindex="-1" aria-labelledby="jsonDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jsonDetailModalLabel">Detalle Completo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 id="jsonModalFieldKey" class="mb-2"></h6>
                    <div id="jsonModalContent" class="json-formatter-container"
                         style="max-height: 70vh; overflow-y: auto; background-color: #f8f9fa; border-radius: 4px; padding: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/json-formatter-js@2.3.4/dist/json-formatter.min.css">
    <script src="https://cdn.jsdelivr.net/npm/json-formatter-js@2.5.23/dist/json-formatter.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/json-formatter-js@2.5.23/dist/json-formatter.min.css" rel="stylesheet">

    <script>
        // Función para decodificar entidades HTML
        function decodeHtmlEntities(text) {
            if (text === null || typeof text === 'undefined') { return ""; }
            var textArea = document.createElement('textarea');
            textArea.innerHTML = text;
            return textArea.value;
        }

        // Función para ordenar claves de objetos JSON recursivamente
        function sortObjectKeysRecursively(obj) {
            if (typeof obj !== 'object' || obj === null) { return obj; }
            if (Array.isArray(obj)) { return obj.map(item => sortObjectKeysRecursively(item)); }
            const sortedKeys = Object.keys(obj).sort((a, b) => a.localeCompare(b));
            const result = {};
            for (const key of sortedKeys) { result[key] = sortObjectKeysRecursively(obj[key]); }
            return result;
        }

        $(document).ready(function () {
            if ($('#spatieActivityLogTable').length && !$.fn.dataTable.isDataTable('#spatieActivityLogTable')) {
                $('#spatieActivityLogTable').DataTable({
                    "language": {"url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"},
                    "scrollX": true,
                    "order": [[6, "desc"]] // Ordenar por la columna de Fecha (índice 6)
                });
            }

            var jsonDetailModalEl = document.getElementById('jsonDetailModal');
            var modalInstance = null;
            if (jsonDetailModalEl) {
                modalInstance = new bootstrap.Modal(jsonDetailModalEl);
            }

            var modalJsonContainer = document.getElementById('jsonModalContent');

            // Usar el ID de la nueva tabla para delegar el evento
            $('#spatieActivityLogTable').on('click', '.view-full-json', function (event) {
                event.preventDefault();

                if (!modalInstance || !modalJsonContainer) {
                    console.error('Instancia del modal o contenedor JSON no inicializados.');
                    return;
                }

                var button = $(this);
                var encodedJsonContent = button.data('json-content');
                var jsonKey = button.data('json-key'); // Este será "Propiedades (Log ID: X)"
                var decodedJsonContent = decodeHtmlEntities(encodedJsonContent);

                var modalTitle = jsonDetailModalEl.querySelector('.modal-title');
                var modalFieldKey = jsonDetailModalEl.querySelector('#jsonModalFieldKey');

                modalTitle.textContent = 'Detalle Completo'; // Título genérico
                modalFieldKey.textContent = jsonKey; // Muestra "Propiedades (Log ID: X)"

                modalJsonContainer.innerHTML = ''; // Limpiar contenido anterior

                try {
                    const jsonObject = JSON.parse(decodedJsonContent);
                    if (typeof jsonObject === 'object' && jsonObject !== null) {
                        const potentiallySortedJsonObject = sortObjectKeysRecursively(jsonObject);
                        const formatter = new JSONFormatter(potentiallySortedJsonObject, 1, {
                            hoverPreviewEnabled: false,
                            theme: ''
                        });
                        modalJsonContainer.appendChild(formatter.render());
                    } else {
                        const textNode = document.createElement('pre');
                        textNode.textContent = decodedJsonContent;
                        modalJsonContainer.appendChild(textNode);
                    }
                } catch (e) {
                    console.warn("El contenido no es JSON válido o hubo un error al formatearlo:", e);
                    const textNode = document.createElement('pre');
                    textNode.textContent = decodedJsonContent;
                    modalJsonContainer.appendChild(textNode);
                }
                modalInstance.show();
            });
        });
    </script>
@endsection
