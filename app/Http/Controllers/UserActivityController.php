<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;
class UserActivityController extends Controller
{
    public function listUsers(Request $request)
    {
        // 1. Obtener la lista de usuarios para la tabla superior (siempre)
        $users_list_table = User::orderBy('name')->get();

        // 2. Datos para los dropdowns del formulario de búsqueda de auditoría (siempre los pasamos para el primer renderizado del form)
        $available_audit_events = Audit::select('event')->distinct()->orderBy('event')->pluck('event');
        $available_audit_models = Audit::select('auditable_type')
            ->whereNotNull('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type');
        $audited_user_ids = Audit::select('user_id')
            ->where('user_type', User::class)
            ->distinct();

        $users_for_audit_filter = User::whereIn('id', $audited_user_ids)
            ->orderBy('name')
            ->pluck('name', 'id');


        // La vista inicial no carga los audits; se cargarán vía AJAX.
        return view('users.activity.users_list', [
            'users' => $users_list_table,
            'available_events' => $available_audit_events,
            'available_models' => $available_audit_models,
            'users_for_filter' => $users_for_audit_filter
        ]);
    }

    /**
     * NUEVO MÉTODO: Obtiene los registros de auditoría global filtrados para AJAX.
     */
    public function fetchGlobalAudits(Request $request)
    {
        // Determinar si se ha aplicado algún filtro significativo desde el formulario
        $hasActiveFilters = $request->filled('date_from_audit') ||
            $request->filled('date_to_audit') ||
            $request->filled('event_type_audit') ||
            $request->filled('model_type_audit') || // model_id usualmente depende de model_type
            $request->filled('user_id_audit') ||
            $request->filled('model_id_audit'); // Si model_id solo es un filtro activo

        // Si no hay filtros activos Y NO quieres cargar todos los datos por defecto en una búsqueda "vacía"
        // (deferLoading ya previene la carga inicial, esto es para búsquedas "vacías" subsiguientes)
        if (!$hasActiveFilters) {
            // Devuelve un conjunto de datos vacío si no hay filtros y esa es la lógica deseada.
            // DataTables espera una clave 'data'.
            return response()->json(['data' => []]);
        }

        $query = Audit::with('user'); // Cargar la relación con el usuario que causó el audit

        // Aplicar filtros basados en los parámetros de la petición AJAX
        if ($request->filled('date_from_audit')) {
            $query->whereDate('created_at', '>=', $request->date_from_audit);
        }
        if ($request->filled('date_to_audit')) {
            $query->whereDate('created_at', '<=', $request->date_to_audit);
        }
        if ($request->filled('event_type_audit')) {
            $query->where('event', $request->event_type_audit);
        }
        if ($request->filled('model_type_audit')) {
            $modelType = str_replace("\\\\", "\\", $request->model_type_audit);
            $query->where('auditable_type', $modelType);
            if ($request->filled('model_id_audit') && is_numeric($request->model_id_audit)) {
                $query->where('auditable_id', $request->model_id_audit);
            }
        }
        if ($request->filled('user_id_audit') && is_numeric($request->user_id_audit)) {
            $query->where('user_id', $request->user_id_audit);
        }

        $audits = $query->orderBy('created_at', 'desc')->get();

        // Transformar los datos para que DataTables los pueda consumir fácilmente
        // y para pre-procesar los snippets y JSON para el modal
        $dataForTable = $audits->map(function ($audit) {
            $oldValuesData = $this->prepareValuesForModal($audit->old_values);
            $newValuesData = $this->prepareValuesForModal($audit->new_values);

            return [
                'id' => $audit->id,
                'user_name' => $audit->user->name ?? ($audit->user_id ?? 'Sistema/Desconocido'),
                'event_display' => '<span class="badge bg-info text-dark">' . ucfirst($audit->event) . '</span>', // HTML para el badge
                'event_raw' => $audit->event, // Para filtrado/orden si es necesario
                'auditable_model' => $audit->auditable_type ? Str::afterLast($audit->auditable_type, '\\') : 'N/A',
                'auditable_id' => $audit->auditable_id ?? 'N/A',
                'old_values_html' => $this->renderValuesCell($oldValuesData, "Valores Antiguos (Audit ID: {$audit->id})"),
                'new_values_html' => $this->renderValuesCell($newValuesData, "Valores Nuevos (Audit ID: {$audit->id})"),
                'url' => $audit->url ?? 'N/A',
                'ip_address' => $audit->ip_address ?? 'N/A',
                'created_at_formatted' => $audit->created_at->format('d/m/Y H:i:s'),
                // Pasamos el JSON completo por si el render no es suficiente y el JS del modal lo necesita de otra forma
                // Sin embargo, la idea es que data-json-content ya esté en el HTML generado por renderValuesCell
            ];
        });

        return response()->json(['data' => $dataForTable]);
    }

    // Método helper para preparar old/new values (puedes ajustarlo)
    private function prepareValuesForModal($values)
    {
        if (empty($values)) {
            return ['snippet' => 'N/A', 'full' => null, 'original_text' => '', 'is_json_like' => false];
        }
        $collection = collect($values); // $values ya es un array/objeto desde el JSON de la BD
        $fullValue = $collection->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $textForSnippet = preg_replace('/\s+/', ' ', $collection->toJson());
        $snippet = Str::limit($textForSnippet, 70);
        return [
            'snippet' => $snippet,
            'full' => $fullValue,
            'original_text' => $textForSnippet,
            'is_json_like' => true // Asumimos que si no está vacío, es una estructura para el modal
        ];
    }

    // Método helper para renderizar el contenido de la celda de valores
    private function renderValuesCell($data, $jsonKey)
    {
        if ($data['snippet'] === 'N/A') {
            return 'N/A';
        }
        $html = '<div class="snippet-text-container">';
        $html .= '<span class="snippet-text" title="' . htmlspecialchars($data['original_text']) . '">' . htmlspecialchars($data['snippet']) . '</span>';
        $html .= '</div>';
        if ($data['full'] && (strlen($data['original_text']) > strlen(str_replace('...', '', $data['snippet'])) || $data['is_json_like'])) {
            $html .= '<a href="#" class="view-full-json small"
                         data-json-key="' . htmlspecialchars($jsonKey) . '"
                         data-json-content="' . htmlspecialchars($data['full']) . '">
                         (ver más)
                      </a>';
        }
        return $html;
    }


    /**
     * Muestra la actividad de un usuario específico.
     */
    public function showUserActivity(User $user, Request $request)
    {
        $query = Audit::with('auditable')
            ->where('user_type', get_class($user))
            ->where('user_id', $user->id);

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Cambiar paginate() por get() para DataTables del lado del cliente
        $activities = $query->orderBy('created_at', 'desc')->get();

        $available_events = Audit::where('user_type', get_class($user))
            ->where('user_id', $user->id)
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event'); // Renombrado para claridad

        return view('users.activity.user_activity', compact('user', 'activities', 'available_events'));
    }

    public function showUserSpatieActivity(User $user, Request $request)
    {
        $query = Activity::where('causer_type', get_class($user))
            ->where('causer_id', $user->id);

        // Filtro por log_name (ej: http_request, default para cambios de modelo)
        if ($request->filled('log_name_filter')) { // Usar un nombre de parámetro diferente para evitar colisiones
            $query->where('log_name', $request->log_name_filter);
        }

        // Filtro por rango de fechas
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Para DataTables del lado del cliente, obtenemos todos los registros que coinciden con el filtro
        $activities = $query->orderBy('created_at', 'desc')->get();

        // Obtener los log_names disponibles para este usuario para el filtro
        $available_log_names = Activity::where('causer_type', get_class($user))
            ->where('causer_id', $user->id)
            ->select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        return view('users.activity.user_activity_http', compact('user', 'activities', 'available_log_names'));
    }


}
