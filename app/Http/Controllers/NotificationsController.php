<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\User;
use App\Notifications\Bitacora;
use App\Notifications\Mod_Devolucion;
use App\Notifications\Produccion;
use App\Notifications\Programada;
use Illuminate\Http\Request;


class NotificationsController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(10);
        return view('notifications.notificaciones', compact('notifications'));
    }

    public function getNotificationsData()
    {
        // Obtener todas las notificaciones no leídas de tipo Mod_Devolucion para el usuario autenticado
        $notifications = auth()->user()->unreadNotifications()
            ->where(function ($query) {
                $query->where('type', Mod_Devolucion::class)
                ->orWhere('type', Bitacora::class)
                ->orWhere('type', Programada::class)
                ->orWhere('type', Produccion::class);
            })
            ->get();

        // Crear contenido para el dropdown
        $dropdownHtml = '<div style="max-height: 400px; overflow-y: auto;">';
        foreach ($notifications as $key => $notification) {
            $dropdownHtml .= '<div id="' . $notification->id . '" class="dropdown-item" style="display: flex; align-items: flex-start; justify-content: space-between; flex-direction: column; padding: 8px 12px; white-space: normal; overflow: hidden;">'
                                    . '<div style="display: flex; align-items: center; width: 100%;">'
                                    . '<i class="mr-2 text-sm ' . $notification->data['icon'] . '"></i>'
                                    . '<span style="flex-grow: 1; font-weight: bold;">' . $notification->data['text'] . '</span>'
                                    . '<i class="fa fa-trash text-sm" id="notificationTrash_' . $notification->id . '" style="cursor: pointer; margin-left: auto; pointer-events: auto;"></i>' // Habilitar interacciones en el ícono de eliminar
                                    . '</div>'
                                    . '<div style="display: flex; justify-content: space-between; width: 100%; margin-top: 4px;">'
                                    . '<span class="text-muted text-sm">' . $notification->data['user'] . " - " . $notification->created_at->diffForHumans() . '</span>'
                                    . '</div>'
                                    . '<a href="' . $notification->data['link'] . '" class="text-muted text-sm" style="text-decoration: none; padding: 5px 0; display: block; pointer-events: auto;">Ver más</a>' // Habilitar interacciones en el enlace "Ver más"
                            . '</div>';

            if ($key < $notifications->count() - 1) {
                $dropdownHtml .= '<div class="dropdown-divider"></div>';
            }
        }

        return [
            'label' => $notifications->count(),
            'label_color' => 'danger',
            'icon_color' => 'dark',
            'dropdown' => $dropdownHtml,
        ];
    }

    public function markAsRead(Request $request)
    {
        try {
            $notificationId = $request->notification_id; // Obtén el ID de la notificación

            $notification = auth()->user()->unreadNotifications()
                ->where('id', $notificationId)
                ->first();

            if ($notification) {
                $notification->markAsRead();
            }

            return response()->json(['success' => true, 'notification_id' => $notificationId]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function markAllAsRead()
    {
        try {
            auth()->user()->unreadNotifications->markAsRead(); // Marcar todas como leídas

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function manage()
    {
        $users = User::with(['roles', 'notificationsMail'])->get();
        return view('notifications.gestionNotificaciones', compact('users'));
    }

    public function getUserNotifications(Request $request)
    {
        $id = $request->input('id');

        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Obtener todas las notificaciones disponibles en la base de datos
        $allNotifications = Notificacion::all()->map(function ($notification) {
            return ['Nombre' => $notification->Nombre, 'label' => $notification->Nombre];
        });

        // Obtener las notificaciones ya asignadas al usuario
        $userNotifications = $user->notificationsMail->map(function ($notification) {
            return ['Nombre' => $notification->Nombre, 'label' => $notification->Nombre];
        });

        // Determinar las notificaciones disponibles que aún no están asignadas
        $assignedNames = $userNotifications->pluck('Nombre')->toArray();
        $availableNotifications = $allNotifications->reject(function ($notification) use ($assignedNames) {
            return in_array($notification['Nombre'], $assignedNames);
        });

        return response()->json([
            'asignadas' => $userNotifications,
            'disponibles' => $availableNotifications->values(),
        ]);
    }

    public function update(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);

            // Obtener notificaciones seleccionadas en el formulario
            $assignedNotifications = $request->assignedNotifications ?? [];
            $revokedNotifications = $request->revokedNotifications ?? [];

            // Obtener IDs de las notificaciones seleccionadas
            $assignedNotificationIds = Notificacion::whereIn('Nombre', $assignedNotifications)->pluck('id')->toArray();
            $revokedNotificationIds = Notificacion::whereIn('Nombre', $revokedNotifications)->pluck('id')->toArray();

            // Asignar nuevas notificaciones
            $user->notificationsMail()->syncWithoutDetaching($assignedNotificationIds);

            // Revocar notificaciones eliminadas
            $user->notificationsMail()->detach($revokedNotificationIds);

            return response()->json([
                'status' => 'success',
                'message' => 'Notificaciones actualizadas correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar las notificaciones: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255|unique:tbl_notificaciones,nombre',
            ]);

            $notificacion = new Notificacion();
            $notificacion->Nombre = $request->nombre;
            $notificacion->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Notificación creada correctamente',
                'user_id' => auth()->id(), // Opcional, para actualizar la tabla sin recargar
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear la notificación: ' . $e->getMessage()
            ], 500);
        }
    }

}
