<?php

namespace App\Http\Controllers;

use App\Notifications\Mod_Devolucion;
use App\Notifications\Bitacora;
use App\Notifications\Programada;
use App\Notifications\Produccion;
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
        $dropdownHtml = '';
        foreach ($notifications as $key => $notification) {
            $dropdownHtml .= '<div id="' . $notification->id . '" class="dropdown-item" style="display: flex; align-items: flex-start; justify-content: space-between; flex-direction: column; padding: 8px 12px; white-space: normal; overflow: hidden; pointer-events: none;">'
                                    . '<div style="display: flex; align-items: center; width: 100%;">'
                                    . '<i class="mr-2 text-sm ' . $notification->data['icon'] . '"></i>'
                                    . '<span style="flex-grow: 1; font-weight: bold;">' . $notification->data['text'] . '</span>'
                                    . '<i class="fa fa-trash text-sm" id="notificationTrash_' . $notification->id . '" style="cursor: pointer; margin-left: auto; pointer-events: auto;"></i>' // Habilitar interacciones en el ícono de eliminar
                                    . '</div>'
                                    . '<div style="display: flex; justify-content: space-between; width: 100%; margin-top: 4px;">'
                                    . '<span class="text-muted text-sm">' . $notification->data['user'] . " " . $notification->created_at->diffForHumans() . '</span>'
                                    . '<a href="' . $notification->data['link'] . '" class="text-muted text-sm" style="text-decoration: none; pointer-events: auto;">Ver más</a>' // Habilitar interacciones en el enlace "Ver más"
                                . '</div>'
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
            $data = $request->input('data');
            $notificationId = $request->notification_id; // Obtén el ID de la notificación

            $notification = auth()->user()->unreadNotifications()
                ->where('id', $notificationId) // Busca la notificación por ID
                ->first(); // Obtén la primera notificación que coincida (o null si no se encuentra)

            if ($notification) {
                $notification->markAsRead(); // Marca la notificación como leída
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        if ($data == 1) {
            $notificationsHtml = $this->getNotificationsData();
            return response()->json(['success' => true, 'notifications' => $notificationsHtml]);
        } else {
            return response()->json(['success' => true]);
        }
    }
}
