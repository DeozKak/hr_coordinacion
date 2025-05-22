@extends('adminlte::page')

@section('title', 'Actividad Usuarios')

@section('content_header')
    <h1></h1>
@endsection

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/json-formatter-js@2.3.4/dist/json-formatter.min.css">
    <script src="https://cdn.jsdelivr.net/npm/json-formatter-js@2.5.23/dist/json-formatter.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/json-formatter-js@2.5.23/dist/json-formatter.min.css" rel="stylesheet">
    <style>
        .dataTables_wrapper {
            width: 100%;
        }

        #activityLogTable th,
        #activityLogTable td {
            white-space: nowrap; /* Las celdas intentan no dividirse por defecto, DataTables scrollX lo maneja */
            vertical-align: top;
        }

        /* Contenedor de la lista dentro de las celdas de valores */
        #activityLogTable td.values-cell ul {
            margin: 0;
            padding-left: 15px;
            list-style-position: outside;
            white-space: normal; /* Permite que los <li> se apilen verticalmente */
        }

        /* Cada item de la lista (key: snippet + ver más) */
        #activityLogTable td.values-cell ul li {
            margin-bottom: 3px;
            display: flex; /* Usamos flex para alinear el snippet y el enlace */
            align-items: baseline; /* Alinea la base del texto del snippet y el enlace */
            /* white-space: nowrap;  El nowrap se manejará en los hijos */
        }

        /* El span que contiene el texto del snippet */
        #activityLogTable td.values-cell .snippet-text {
            display: inline-block; /* Ocupa solo el espacio necesario */
            max-width: 280px; /* ANCHO MÁXIMO PARA EL FRAGMENTO ANTES DE "..." */
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap; /* El snippet en sí mismo intenta estar en una línea hasta el max-width */
            vertical-align: baseline; /* Para alinearse con el enlace "ver más" */
        }

        /* El enlace "ver más" */
        #activityLogTable td.values-cell .view-full-json {
            margin-left: 8px; /* Espacio entre el snippet y el enlace */
            white-space: nowrap; /* Asegura que el texto "(ver más)" no se divida */
            flex-shrink: 0; /* Evita que el enlace se encoja si no hay espacio */
        }

        /* Estilo para el contenido preformateado en el MODAL (sin cambios) */
        #jsonModalContent {
            white-space: pre-wrap;
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>

    <div class="container-fluid">
        <h1>Actividad de: {{ $user->name }} ({{ $user->email }})</h1>

        {{-- Formulario de Filtros (sin cambios) --}}
        <form method="GET" action="{{ route('admin.user.activity.show', $user) }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <label for="event_filter">Evento:</label>
                    <select name="event" id="event_filter" class="form-control form-select">
                        <option value="">Todos</option>
                        @foreach($available_events as $event_option)
                            <option
                                value="{{ $event_option }}" {{ request('event') == $event_option ? 'selected' : '' }}>
                                {{ ucfirst($event_option) }}
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
                    <a href="{{ route('admin.user.activity.show', $user) }}" class="btn btn-secondary mt-4">Limpiar</a>
                </div>
            </div>
        </form>

        @if($activities->isEmpty() && (request()->has('event') || request()->has('date_from') || request()->has('date_to')))
            <p>No hay actividad registrada para este usuario con los filtros actuales.</p>
        @elseif($activities->isEmpty())
            <p>No hay actividad registrada para este usuario.</p>
        @else
            <table class="table table-striped table-hover display" id="activityLogTable" style="width:100%">
                <thead>
                <tr>
                    <th>ID Log</th>
                    <th>Evento</th>
                    <th>Modelo Afectado</th>
                    <th>ID Modelo</th>
                    <th>Valores Antiguos</th>
                    <th>Valores Nuevos</th>
                    <th>URL</th>
                    <th>IP</th>
                    <th>Fecha y Hora</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($activities as $activity)
                    @php
                        $oldValuesSnippets = [];
                        $oldValuesFullJsonForModal = [];
                        if (!empty($activity->old_values)) {
                            foreach ($activity->old_values as $key => $value) {
                                $isJsonLike = is_array($value) || is_object($value);
                                $fullValue = $isJsonLike ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $value;
                                $textForSnippet = $isJsonLike ? json_encode($value) : (string) $value; // JSON sin pretty print para el snippet
                                $textForSnippet = preg_replace('/\s+/', ' ', $textForSnippet); // Colapsar espacios
                                $snippet = Str::limit($textForSnippet, 70);
                                $oldValuesSnippets[$key] = $snippet;
                                $oldValuesFullJsonForModal[$key] = ['full' => $fullValue, 'is_json' => $isJsonLike, 'original_for_comparison' => $textForSnippet];
                            }
                        }

                        $newValuesSnippets = [];
                        $newValuesFullJsonForModal = [];
                        if (!empty($activity->new_values)) {
                            foreach ($activity->new_values as $key => $value) {
                                $isJsonLike = is_array($value) || is_object($value);
                                $fullValue = $isJsonLike ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $value;
                                $textForSnippet = $isJsonLike ? json_encode($value) : (string) $value;
                                $textForSnippet = preg_replace('/\s+/', ' ', $textForSnippet);
                                $snippet = Str::limit($textForSnippet, 70);
                                $newValuesSnippets[$key] = $snippet;
                                $newValuesFullJsonForModal[$key] = ['full' => $fullValue, 'is_json' => $isJsonLike, 'original_for_comparison' => $textForSnippet];
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $activity->id }}</td>
                        <td><span class="badge bg-info text-dark">{{ ucfirst($activity->event) }}</span></td>
                        <td>{{ $activity->auditable_type ? Str::afterLast($activity->auditable_type, '\\') : 'N/A' }}</td>
                        <td>{{ $activity->auditable_id ?? 'N/A' }}</td>
                        <td class="values-cell"> {{-- Valores Antiguos --}}
                            @if (!empty($oldValuesSnippets))
                                <ul>
                                    @foreach ($oldValuesSnippets as $key => $snippet)
                                        <li>
                                            <div class="snippet-text-container">
                                                <strong>{{ $key }}:</strong>
                                                <span class="snippet-text"
                                                      title="{{ $oldValuesFullJsonForModal[$key]['original_for_comparison'] }}">{{ $snippet }}</span>
                                            </div>
                                            @if(strlen($oldValuesFullJsonForModal[$key]['original_for_comparison']) > strlen(str_replace('...', '', $snippet)) || $oldValuesFullJsonForModal[$key]['is_json'])
                                                <a href="#" class="view-full-json small"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#jsonDetailModal"
                                                   data-json-key="{{ $key }}"
                                                   data-json-content="{{ htmlspecialchars($oldValuesFullJsonForModal[$key]['full']) }}">
                                                    (ver más)
                                                </a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="values-cell"> {{-- Valores Nuevos --}}
                            @if (!empty($newValuesSnippets))
                                <ul>
                                    @foreach ($newValuesSnippets as $key => $snippet)
                                        <li>
                                            <div class="snippet-text-container">
                                                <strong>{{ $key }}:</strong>
                                                <span class="snippet-text"
                                                      title="{{ $newValuesFullJsonForModal[$key]['original_for_comparison'] }}">{{ $snippet }}</span>
                                            </div>
                                            @if(strlen($newValuesFullJsonForModal[$key]['original_for_comparison']) > strlen(str_replace('...', '', $snippet)) || $newValuesFullJsonForModal[$key]['is_json'])
                                                <a href="#" class="view-full-json small"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#jsonDetailModal"
                                                   data-json-key="{{ $key }}"
                                                   data-json-content="{{ htmlspecialchars($newValuesFullJsonForModal[$key]['full']) }}">
                                                    (ver más)
                                                </a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $activity->url ?? 'N/A' }}</td>
                        <td>{{ $activity->ip_address ?? 'N/A' }}</td>
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

    <div class="modal fade" id="jsonDetailModal" tabindex="-1" aria-labelledby="jsonDetailModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jsonDetailModalLabel">Detalle Completo del Campo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 id="jsonModalFieldKey" class="mb-2"></h6>
                    {{-- Cambiado de <pre> a <div> --}}
                    <div id="jsonModalContent" class="json-formatter-container"
                         style="max-height: 70vh; overflow-y: auto; background-color: #f8f9fa; border-radius: 4px; padding: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')

    <script>
        // Función para decodificar entidades HTML (sin cambios)
        function decodeHtmlEntities(text) {
            if (text === null || typeof text === 'undefined') {
                return "";
            }
            var textArea = document.createElement('textarea');
            textArea.innerHTML = text;
            return textArea.value;
        }

        // Función para ordenar claves de objetos JSON recursivamente (sin cambios)
        function sortObjectKeysRecursively(obj) {
            if (typeof obj !== 'object' || obj === null) {
                return obj;
            }
            if (Array.isArray(obj)) {
                return obj.map(item => sortObjectKeysRecursively(item));
            }
            const sortedKeys = Object.keys(obj).sort((a, b) => a.localeCompare(b));
            const result = {};
            for (const key of sortedKeys) {
                result[key] = sortObjectKeysRecursively(obj[key]);
            }
            return result;
        }

        $(document).ready(function () {
            // Inicialización de DataTables (sin cambios)
            if ($('#activityLogTable').length && !$.fn.dataTable.isDataTable('#activityLogTable')) {
                $('#activityLogTable').DataTable({
                    "language": {"url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"},
                    "scrollX": true,
                    "order": [[8, "desc"]]
                });
            }

            var jsonDetailModalEl = document.getElementById('jsonDetailModal');
            var modalInstance = null;
            if (jsonDetailModalEl) {
                modalInstance = new bootstrap.Modal(jsonDetailModalEl);
            }

            // Elemento donde se renderizará el JSON interactivo
            var modalJsonContainer = document.getElementById('jsonModalContent');

            $('#activityLogTable').on('click', '.view-full-json', function (event) {
                event.preventDefault();

                if (!modalInstance || !modalJsonContainer) {
                    console.error('Instancia del modal o contenedor JSON no inicializados.');
                    return;
                }

                var button = $(this);
                var encodedJsonContent = button.data('json-content');
                var jsonKey = button.data('json-key');
                var decodedJsonContent = decodeHtmlEntities(encodedJsonContent);

                var modalTitle = jsonDetailModalEl.querySelector('.modal-title');
                var modalFieldKey = jsonDetailModalEl.querySelector('#jsonModalFieldKey');

                modalTitle.textContent = 'Detalle del campo: ' + jsonKey;
                modalFieldKey.textContent = 'Campo: ' + jsonKey;

                // Limpiar contenido anterior del contenedor JSON
                modalJsonContainer.innerHTML = '';

                try {
                    const jsonObject = JSON.parse(decodedJsonContent);

                    if (typeof jsonObject === 'object' && jsonObject !== null) {
                        const potentiallySortedJsonObject = sortObjectKeysRecursively(jsonObject); // Ordenar claves si lo deseas

                        // Configuración para json-formatter-js
                        // open: Número de niveles a expandir por defecto.
                        // theme: 'dark' (opcional, si incluyes su CSS de tema oscuro)
                        const formatter = new JSONFormatter(potentiallySortedJsonObject, 1, {
                            hoverPreviewEnabled: false,
                            theme: ''
                        });

                        modalJsonContainer.appendChild(formatter.render());
                    } else {
                        // Si no es un objeto JSON (ej. un string o número), mostrarlo como texto simple
                        const textNode = document.createElement('pre');
                        textNode.textContent = decodedJsonContent;
                        modalJsonContainer.appendChild(textNode);
                    }
                } catch (e) {
                    // Si no es JSON válido, mostrar como texto simple
                    console.warn("El contenido no es JSON válido o hubo un error al formatearlo:", e);
                    const textNode = document.createElement('pre');
                    textNode.textContent = decodedJsonContent; // Mostrar el contenido decodificado tal cual
                    modalJsonContainer.appendChild(textNode);
                }

                modalInstance.show();
            });
        });
    </script>
@endsection
