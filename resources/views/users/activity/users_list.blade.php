@extends('adminlte::page')

@section('title', 'Usuarios y Auditoría')

@section('content_header')
    <h1>Gestión de Usuarios y Búsqueda de Auditoría</h1>
@endsection

@section('styles')

    <style>
        /* ... (tus estilos CSS existentes para las tablas y el modal) ... */
        .dataTables_wrapper { width: 100%; }
        #usersTable th, #usersTable td,
        #auditLogTable th, #auditLogTable td {
            white-space: nowrap;
            vertical-align: top;
        }
        #auditLogTable td.values-cell { /* Ajustado para que el render HTML del controller funcione */
            white-space: normal; /* Permitir que el contenido interno con flex se maneje bien */
        }
        #auditLogTable td.values-cell ul li, /* Si sigues usando UL/LI, sino ajustar */
        #auditLogTable td.values-cell div.snippet-text-container + a.view-full-json { /* Para el render sin UL/LI */
            display: flex;
            align-items: baseline;
        }
        #auditLogTable td.values-cell .snippet-text-container { display: inline; }
        #auditLogTable td.values-cell .snippet-text { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: baseline; }
        #auditLogTable td.values-cell .view-full-json { margin-left: 8px; white-space: nowrap; flex-shrink: 0; }

        #jsonModalContent { white-space: pre-wrap; word-break: break-all; background-color: #f8f9fa; padding: 15px; border-radius: 4px; max-height: 70vh; overflow-y: auto; }
        .card { margin-top: 20px; }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        {{-- Card para Lista de Usuarios (sin cambios significativos en su HTML) --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Lista de Usuarios</h3></div>
            <div class="card-body">
                <table class="table table-bordered table-striped" id="usersTable" style="width:100%;">
                    <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $user_item)
                        <tr>
                            <td>{{ $user_item->id }}</td>
                            <td>{{ $user_item->name }}</td>
                            <td>{{ $user_item->email }}</td>
                            <td>
                                <a href="{{ route('admin.user.activity.show', $user_item) }}" class="btn btn-xs btn-info">Actividad BD</a>
                                <a href="{{ route('admin.user.http_activity.show', $user_item) }}" class="btn btn-xs btn-warning">Actividad HTTP</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No se encontraron usuarios.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Card para Buscador Global de Auditoría --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Buscador Global de Auditoría de Base de Datos</h3></div>
            <div class="card-body">
                {{-- El action ya no es necesario, o puede ser # --}}
                <form method="GET" action="#" id="auditSearchForm" class="mb-4">
                    {{-- Campos del formulario como antes (date_from_audit, event_type_audit, etc.) --}}
                    {{-- Asegúrate que los name de los input coincidan con lo que espera el controlador fetchGlobalAudits --}}
                    <div class="row">
                        <div class="col-md-3">
                            <label for="date_from_audit">Desde Fecha:</label>
                            <input type="date" name="date_from_audit" id="date_from_audit" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to_audit">Hasta Fecha:</label>
                            <input type="date" name="date_to_audit" id="date_to_audit" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="event_type_audit">Tipo de Evento:</label>
                            <select name="event_type_audit" id="event_type_audit" class="form-control form-select">
                                <option value="">Todos</option>
                                @foreach($available_events as $event)
                                    <option value="{{ $event }}">{{ ucfirst($event) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="user_id_audit">Usuario (Causante):</label>
                            <select name="user_id_audit" id="user_id_audit" class="form-control form-select">
                                <option value="">Todos</option>
                                @foreach($users_for_filter as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label for="model_type_audit">Tipo de Modelo:</label>
                            <select name="model_type_audit" id="model_type_audit" class="form-control form-select">
                                <option value="">Todos</option>
                                @foreach($available_models as $model_name)
                                    <option value="{{ $model_name }}">{{ Str::afterLast($model_name, '\\') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="model_id_audit">ID del Modelo:</label>
                            <input type="number" name="model_id_audit" id="model_id_audit" class="form-control" placeholder="ID del registro afectado">
                        </div>
                        <div class="col-md-3 align-self-end">
                            {{-- Cambiado a type="button" para manejo con JS --}}
                            <button type="button" id="submitAuditSearchBtn" class="btn btn-primary mt-4">Buscar Auditoría</button>
                            <button type="button" id="clearAuditSearchBtn" class="btn btn-secondary mt-4">Limpiar Filtros</button>
                        </div>
                    </div>
                </form>

                <hr>
                <h4 class="mt-3">Resultados de la Auditoría:</h4>
                <table class="table table-bordered table-striped table-hover display" id="auditLogTable" style="width:100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Evento</th>
                        <th>Modelo</th>
                        <th>ID Modelo</th>
                        <th>Valores Antiguos</th>
                        <th>Valores Nuevos</th>
                        <th>URL (Relativa)</th>
                        <th>IP</th>
                        <th>Fecha y Hora</th>
                    </tr>
                    </thead>
                    <tbody>
                    {{-- El contenido se cargará vía AJAX por DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal para ver JSON completo (sin cambios en su estructura HTML) --}}
        <div class="modal fade" id="jsonDetailModal" tabindex="-1" aria-labelledby="jsonDetailModalLabel" aria-hidden="true">
            {{-- ... estructura del modal ... --}}
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="jsonDetailModalLabel">Detalle Completo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6 id="jsonModalFieldKey" class="mb-2"></h6>
                        <div id="jsonModalContent" class="json-formatter-container"></div>
                    </div>
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
        function decodeHtmlEntities(text) { if (text === null || typeof text === 'undefined') { return ""; } var textArea = document.createElement('textarea'); textArea.innerHTML = text; return textArea.value; }
        function sortObjectKeysRecursively(obj) { if (typeof obj !== 'object' || obj === null) { return obj; } if (Array.isArray(obj)) { return obj.map(item => sortObjectKeysRecursively(item)); } const sortedKeys = Object.keys(obj).sort((a, b) => a.localeCompare(b)); const result = {}; for (const key of sortedKeys) { result[key] = sortObjectKeysRecursively(obj[key]); } return result; }

        $(document).ready(function () {
            // DataTable para la lista de usuarios
            if ($('#usersTable').length && !$.fn.dataTable.isDataTable('#usersTable') ) {
                $('#usersTable').DataTable({

                    responsive: true
                });
            }

            // DataTable para los resultados de la auditoría
            var auditLogTable = null;
            if ($('#auditLogTable').length) { // Inicializar siempre, pero se cargará vacío al inicio
                auditLogTable = $('#auditLogTable').DataTable({
                    "processing": true, // Muestra indicador de "Procesando..."
                    // "serverSide": true, // Para paginación y filtros del lado del servidor (más avanzado)
                    "ajax": {
                        "url": "{{ route('admin.global_audit.fetch') }}", // Ruta al endpoint AJAX
                        "type": "GET",
                        "data": function (d) { // 'd' son los parámetros que DataTables envía (para paginación, búsqueda interna etc.)
                            // Añadimos los datos de nuestro formulario de filtro
                            var formData = $('#auditSearchForm').serializeArray();
                            $.each(formData, function(key, val){
                                d[val.name] = val.value;
                            });
                            // console.log('Enviando a DataTables:', d); // Para depurar
                            return d; // O return $('#auditSearchForm').serialize(); si tu backend solo necesita eso
                        },
                        "dataSrc": "data" // Asumiendo que el JSON de respuesta es { "data": [...] }
                    },
                    "deferLoading": 0,
                    "columns": [
                        { "data": "id" },
                        { "data": "user_name" },
                        { "data": "event_display" }, // Usar el HTML pre-renderizado para el badge
                        { "data": "auditable_model" },
                        { "data": "auditable_id" },
                        { "data": "old_values_html", "className": "values-cell" }, // Usar el HTML pre-renderizado
                        { "data": "new_values_html", "className": "values-cell" }, // Usar el HTML pre-renderizado
                        { "data": "url" },
                        { "data": "ip_address" },
                        { "data": "created_at_formatted" }
                    ],
                    "order": [[9, "desc"]], // Ordenar por Fecha (índice 9)
                    "scrollX": true,
                    responsive: true
                });
            }

            // Manejar envío del formulario de búsqueda de auditoría
            $('#submitAuditSearchBtn').on('click', function() {
                if (auditLogTable) {
                    auditLogTable.ajax.reload(); // Recarga los datos de la tabla usando los filtros actuales del formulario
                }
            });

            // Manejar limpieza de filtros
            $('#clearAuditSearchBtn').on('click', function() {
                $('#auditSearchForm')[0].reset(); // Limpia los campos del formulario
                if (auditLogTable) {
                    auditLogTable.ajax.reload(); // Recarga la tabla, ahora con filtros vacíos
                }
            });

            // Modal para ver JSON completo (delegado al cuerpo de la tabla de auditoría)
            var jsonDetailModalEl = document.getElementById('jsonDetailModal');
            var modalInstance = null;
            if (jsonDetailModalEl) {  modalInstance = new bootstrap.Modal(jsonDetailModalEl); }
            var modalJsonContainer = document.getElementById('jsonModalContent');

            $('#auditLogTable tbody').on('click', '.view-full-json', function (event) {
                event.preventDefault();
                if (!modalInstance || !modalJsonContainer) {
                    console.error('Modal o contenedor JSON no inicializados.');
                    return;
                }

                var button = $(this);
                var dataFromAttribute = button.data('json-content'); // jQuery puede devolver esto ya como objeto
                var jsonKey = button.data('json-key');

                var modalTitle = jsonDetailModalEl.querySelector('.modal-title');
                var modalFieldKey = jsonDetailModalEl.querySelector('#jsonModalFieldKey');
                modalTitle.textContent = 'Detalle Completo';
                modalFieldKey.textContent = jsonKey;
                modalJsonContainer.innerHTML = ''; // Limpiar contenido anterior

                // console.log("Contenido del data-attribute:", dataFromAttribute); // Para depurar qué tipo es

                try {
                    let jsonObjectToFormat;

                    if (typeof dataFromAttribute === 'string') {
                        // Si es un string, entonces sí necesitamos decodificar entidades (si no lo hizo .data()) y parsear
                        // La función decodeHtmlEntities es importante aquí si el string aún tiene &quot; etc.
                        // pero jQuery .data() suele manejar bien la decodificación de atributos puestos con htmlspecialchars
                        // y luego parsea si es un JSON string válido.
                        // Por si acaso, si es string, asumimos que es un JSON string que necesita parseo.
                        jsonObjectToFormat = JSON.parse(dataFromAttribute);
                    } else if (typeof dataFromAttribute === 'object' && dataFromAttribute !== null) {
                        // Si ya es un objeto (como tu console.log indicó), úsalo directamente
                        jsonObjectToFormat = dataFromAttribute;
                    } else {
                        // Si es null, undefined, o un tipo primitivo que no es string (número, boolean)
                        // lo mostramos tal cual, no es un objeto para JSONFormatter.
                        const textNode = document.createElement('pre');
                        textNode.textContent = String(dataFromAttribute); // Convertir a string para mostrar
                        modalJsonContainer.appendChild(textNode);
                        modalInstance.show();
                        return; // Salir temprano
                    }

                    // Ahora jsonObjectToFormat debería ser un objeto JavaScript válido
                    const potentiallySortedJsonObject = sortObjectKeysRecursively(jsonObjectToFormat);
                    const formatter = new JSONFormatter(potentiallySortedJsonObject, 1, {
                        hoverPreviewEnabled: false,
                        theme: ''
                    });
                    modalJsonContainer.appendChild(formatter.render());

                } catch (e) {
                    // Este catch es para errores en JSON.parse (si era un string pero JSON inválido)
                    // o errores en sortObjectKeysRecursively o JSONFormatter.
                    console.warn("Contenido no es JSON válido o error al formatear:", e, "Contenido original del atributo:", dataFromAttribute);
                    const textNode = document.createElement('pre');
                    // Mostrar el contenido original (o su representación string si era un objeto que falló después)
                    textNode.textContent = (typeof dataFromAttribute === 'string') ? dataFromAttribute : JSON.stringify(dataFromAttribute, null, 2);
                    modalJsonContainer.appendChild(textNode);
                }

                modalInstance.show();
            });
        });
    </script>
@endsection
