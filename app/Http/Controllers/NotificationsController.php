<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function getNotificationsData(Request $request)
{
   

    // Obtener las notificaciones de tipo NuevaDevolucion
    $notifications = auth()->user()->notifications()
        ->where('type', 'App\Notifications\Mod_Devolucion')
        ->get();

    // Now, we create the notification dropdown main content.

    $dropdownHtml = '';

    foreach ($notifications as $key => $not) {
        $icon = "<i class='mr-2 {$not['icon']}'></i>";

        $time = "<span class='float-right text-muted text-sm'>
                   {$not['time']}
                 </span>";

        $dropdownHtml .= "<a href='#' class='dropdown-item'>
                            {$icon}{$not['text']}{$time}
                          </a>";

        if ($key < count($notifications) - 1) {
            $dropdownHtml .= "<div class='dropdown-divider'></div>";
        }
    }

    // Return the new notification data.

    return [
        'label' => count($notifications),
        'label_color' => 'danger',
        'icon_color' => 'dark',
        'dropdown' => $dropdownHtml,
    ];
}
}
