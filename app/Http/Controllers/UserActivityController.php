<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use OwenIt\Auditing\Models\Audit; // El modelo de Auditoría
class UserActivityController extends Controller
{
    public function listUsers()
    {
        $users = User::orderBy('name')->get(); // Obtén tus usuarios
        return view('users.activity.users_list', compact('users'));
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
}
