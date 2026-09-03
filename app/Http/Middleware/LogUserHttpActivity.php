<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\Activity;

class LogUserHttpActivity
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Dejar que la petición continúe primero para poder obtener el status de la respuesta si se desea
        $response = $next($request);

        if (Auth::check()) { // Solo registrar si hay un usuario autenticado
            $user = Auth::user();
            $logName = 'http_request'; // Un nombre para este tipo de log

            // Información que quieres guardar
            $properties = [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(), // Código de estado de la respuesta
                // 'parameters' => $request->except(['password', 'password_confirmation', '_token']), // Cuidado con datos sensibles
            ];

            // Opcional: Decide si quieres loguear esta petición específica
            if ($this->shouldLogRequest($request)) {
                activity($logName)
                    ->causedBy($user)
                    ->withProperties($properties)
                    ->log("Accedió a la URL: " . $request->path());
            }
        }

        return $response;
    }

    protected function shouldLogRequest(Request $request): bool
    {
        // No registrar peticiones OPTIONS
        if ($request->isMethod('OPTIONS')) {
            return false;
        }

        // Excluir por patrones de URI
        $excludedPaths = [
            'livewire/*',
            'telescope/*',
            'horizon/*',
            '_debugbar/*',
            'assets/*',
            'css/*',
            'js/*',
            'img/*',
            'vendor/*',
            'broadcasting/auth',
            // 'admin/activity-log/*', // Si tienes un visor de logs
            // Añade más patrones de URI si es necesario
        ];

        if ($request->is(...$excludedPaths)) {
            return false;
        }

        // Excluir por NOMBRES DE RUTA
        $excludedRouteNames = [
            'ignition.healthCheck',
            'livewire.update',
            'livewire.message',
            // 'admin.activity.index', // Si tienes un visor de logs con nombre


            /* Sondeos automáticos: se repiten cada minuto por pestaña abierta
               y no dicen nada de lo que hizo la persona. Registrarlos llenaba
               activity_log de ruido —la mitad de la tabla era el sondeo de
               quejas— y convertía cada consulta de lectura en una escritura. */
            'notifications.get',
            'notifications.json',
            'notifications.markAsRead',
            'notifications.markAllAsRead',
            'notifications.index',
            'pqrs.coordinacion.datosActualizados',
            'jobs.pnd',
            'admin.user.http_activity.show',
            'admin.user.activity.show',
            'admin.users.activity.list'


        ];

        if ($request->route() && in_array($request->route()->getName(), $excludedRouteNames)) {
            return false;
        }

        return true; // Registrar por defecto si no cae en ninguna exclusión
    }
}
