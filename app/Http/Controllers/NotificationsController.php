<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\Mod_Devolucion;
use App\Notifications\Bitacora;
use App\Notifications\Programada;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

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
                  ->orWhere('type', Programada::class);
           
        })
        ->get();

        // Crear contenido para el dropdown
        $dropdownHtml = '';
        foreach ($notifications as $key => $notification) {
            $dropdownHtml .= '<a id='.$notification->id.' href='.$notification->data['link'].' class="dropdown-item" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'
                . '<i class="mr-2 text-sm ' . $notification->data['icon'] . '"></i>'
                . $notification->data['text']
                . '<span class="float-right text-muted text-sm">' . $notification->data['user'] ." ".$notification->created_at->diffForHumans() . '</span>'
                . '</a>';

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
        try{
        $notificationId = $request->notification_id; // Obtén el ID de la notificación
       
        $notification = auth()->user()->unreadNotifications()
            ->where('id', $notificationId) // Busca la notificación por ID
            ->first(); // Obtén la primera notificación que coincida (o null si no se encuentra)
        
        if ($notification) {
            $notification->markAsRead(); // Marca la notificación como leída
        }
    }catch(\Exception $e){
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    
    }

        return response()->json(['success' => true]); 
      
    }

}
